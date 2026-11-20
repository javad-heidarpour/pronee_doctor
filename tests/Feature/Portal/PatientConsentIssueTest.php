<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\PatientConsentController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class PatientConsentIssueTest extends TestCase
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
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('clinic_id');
            $table->string('status')->default('confirmed');
            $table->timestamps();
        });

        Schema::create('patient_account_accesses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('user_id');
            $table->string('access_type');
            $table->json('permissions')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_consents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('signed_by_user_id')->nullable();
            $table->string('consent_type');
            $table->string('title');
            $table->text('consent_text');
            $table->string('content_sha256', 64);
            $table->string('status');
            $table->string('signer_name')->nullable();
            $table->string('signature_method')->nullable();
            $table->string('signature_sha256', 64)->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->string('signer_ip_address', 45)->nullable();
            $table->string('signer_user_agent', 500)->nullable();
            $table->timestamps();
        });
    }

    public function test_doctor_can_issue_a_content_hashed_consent_for_owned_patient_with_active_owner_account(): void
    {
        [$userId, $doctorId, $clinicId, $patientId, $appointmentId] = $this->seedContext(owner: true);
        $this->authenticateDoctor($userId, $doctorId);

        $text = 'شرح کامل اقدام درمانی، منافع مورد انتظار، خطرات و گزینه‌های جایگزین برای بیمار.';
        $response = app(PatientConsentController::class)->store(
            Request::create('/portal/patients/'.$patientId.'/consents', 'POST', [
                'clinic_id' => $clinicId,
                'appointment_id' => $appointmentId,
                'consent_type' => 'treatment',
                'title' => 'رضایت اقدام درمانی',
                'consent_text' => $text,
            ]),
            $patientId,
            app(RecordSecurityAuditEventAction::class)
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertDatabaseHas('patient_consents', [
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'clinic_id' => $clinicId,
            'appointment_id' => $appointmentId,
            'status' => 'pending',
            'content_sha256' => hash('sha256', $text),
        ]);
    }

    public function test_doctor_cannot_issue_a_consent_without_active_patient_owner_account(): void
    {
        [$userId, $doctorId, $clinicId, $patientId, $appointmentId] = $this->seedContext(owner: false);
        $this->authenticateDoctor($userId, $doctorId);

        try {
            app(PatientConsentController::class)->store(
                Request::create('/portal/patients/'.$patientId.'/consents', 'POST', [
                    'clinic_id' => $clinicId,
                    'appointment_id' => $appointmentId,
                    'consent_type' => 'surgery',
                    'title' => 'رضایت جراحی',
                    'consent_text' => 'متن کامل رضایت‌نامه جراحی با شرح خطرات و گزینه‌های جایگزین.',
                ]),
                $patientId,
                app(RecordSecurityAuditEventAction::class)
            );
            $this->fail('A consent cannot be issued if the patient cannot access the signing flow.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }

        $this->assertDatabaseCount('patient_consents', 0);
    }

    private function seedContext(bool $owner): array
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $appointmentId = DB::table('appointments')->insertGetId([
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'clinic_id' => $clinicId,
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($owner) {
            DB::table('patient_account_accesses')->insert([
                'patient_id' => $patientId,
                'user_id' => $userId + 100,
                'access_type' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [$userId, $doctorId, $clinicId, $patientId, $appointmentId];
    }

    private function authenticateDoctor(int $userId, int $doctorId): void
    {
        Auth::guard('web')->setUser(User::query()->findOrFail($userId));
        session(['active_doctor_id' => $doctorId]);
    }
}
