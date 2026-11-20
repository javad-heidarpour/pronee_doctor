<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\PatientDocumentController;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PatientDocumentControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('family')->nullable();
            $table->string('role');
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('clinic_id');
            $table->timestamps();
        });

        Schema::create('patient_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('uploaded_by_user_id')->nullable();
            $table->unsignedBigInteger('archived_by_user_id')->nullable();
            $table->string('category');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('original_name');
            $table->string('disk')->default('local');
            $table->string('path')->unique();
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('checksum_sha256');
            $table->timestamp('uploaded_at');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Storage::fake('local');
    }

    public function test_doctor_uploads_document_to_private_storage_and_downloads_only_through_controller(): void
    {
        [$userId, $doctorId, $clinicId, $patientId] = $this->seedDoctorPatient();
        $this->authenticateDoctor($userId);

        $request = Request::create('/portal/patients/'.$patientId.'/documents', 'POST', [
            'clinic_id' => $clinicId,
            'category' => 'lab_result',
            'title' => 'آزمایش خون',
        ], [], [
            'file' => UploadedFile::fake()->create('blood-test.pdf', 100, 'application/pdf'),
        ]);

        $response = app(PatientDocumentController::class)->store(
            $request,
            $patientId,
            app(RecordSecurityAuditEventAction::class)
        );

        $this->assertSame(302, $response->getStatusCode());
        $document = DB::table('patient_documents')->first();
        $this->assertNotNull($document);
        $this->assertSame($doctorId, (int) $document->doctor_id);
        $this->assertSame($patientId, (int) $document->patient_id);
        $this->assertSame($clinicId, (int) $document->clinic_id);
        Storage::disk('local')->assertExists($document->path);
        $this->assertStringStartsWith('patient-documents/'.$doctorId.'/'.$patientId.'/', $document->path);

        $download = app(PatientDocumentController::class)->download(
            $patientId,
            (int) $document->id,
            app(RecordSecurityAuditEventAction::class)
        );

        $this->assertStringContainsString('blood-test.pdf', (string) $download->headers->get('Content-Disposition'));
        $this->assertSame('private, no-store, max-age=0', $download->headers->get('Cache-Control'));
    }

    public function test_document_download_and_archive_cannot_cross_patient_or_doctor_scope(): void
    {
        [$userId, $doctorId, $clinicId, $patientId] = $this->seedDoctorPatient();
        $this->authenticateDoctor($userId);

        $otherPatientId = DB::table('patients')->insertGetId([
            'doctor_id' => $doctorId,
            'name' => 'Other patient',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = 'patient-documents/'.$doctorId.'/'.$patientId.'/private.pdf';
        Storage::disk('local')->put($path, 'private contents');
        $documentId = $this->insertDocument($doctorId, $clinicId, $patientId, $path);

        try {
            app(PatientDocumentController::class)->download(
                $otherPatientId,
                $documentId,
                app(RecordSecurityAuditEventAction::class)
            );
            $this->fail('A document must not be downloadable through another patient route.');
        } catch (ModelNotFoundException $exception) {
            $this->assertInstanceOf(ModelNotFoundException::class, $exception);
        }

        try {
            app(PatientDocumentController::class)->archive(
                $otherPatientId,
                $documentId,
                app(RecordSecurityAuditEventAction::class)
            );
            $this->fail('A document must not be archived through another patient route.');
        } catch (ModelNotFoundException $exception) {
            $this->assertInstanceOf(ModelNotFoundException::class, $exception);
        }

        $this->assertDatabaseHas('patient_documents', [
            'id' => $documentId,
            'archived_at' => null,
        ]);
        Storage::disk('local')->assertExists($path);
    }

    public function test_archiving_hides_document_without_deleting_original_file(): void
    {
        [$userId, $doctorId, $clinicId, $patientId] = $this->seedDoctorPatient();
        $this->authenticateDoctor($userId);

        $path = 'patient-documents/'.$doctorId.'/'.$patientId.'/archive-me.pdf';
        Storage::disk('local')->put($path, 'keep original');
        $documentId = $this->insertDocument($doctorId, $clinicId, $patientId, $path);

        $response = app(PatientDocumentController::class)->archive(
            $patientId,
            $documentId,
            app(RecordSecurityAuditEventAction::class)
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertDatabaseMissing('patient_documents', [
            'id' => $documentId,
            'archived_at' => null,
        ]);
        $this->assertDatabaseHas('patient_documents', [
            'id' => $documentId,
            'archived_by_user_id' => $userId,
        ]);
        Storage::disk('local')->assertExists($path);
    }

    private function seedDoctorPatient(): array
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Doctor',
            'role' => 'doctor',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doctorId = DB::table('doctors')->insertGetId([
            'user_id' => $userId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctorId,
            'name' => 'Clinic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $patientId = DB::table('patients')->insertGetId([
            'doctor_id' => $doctorId,
            'name' => 'Patient',
            'phone' => '09120000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('appointments')->insert([
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'clinic_id' => $clinicId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$userId, $doctorId, $clinicId, $patientId];
    }

    private function authenticateDoctor(int $userId): void
    {
        Auth::guard('web')->setUser(User::query()->findOrFail($userId));
        session(['active_doctor_id' => DB::table('doctors')->where('user_id', $userId)->value('id')]);
    }

    private function insertDocument(int $doctorId, int $clinicId, int $patientId, string $path): int
    {
        return DB::table('patient_documents')->insertGetId([
            'doctor_id' => $doctorId,
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'category' => 'lab_result',
            'title' => 'Private document',
            'original_name' => 'private.pdf',
            'disk' => 'local',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'file_size' => 16,
            'checksum_sha256' => hash('sha256', 'private contents'),
            'uploaded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
