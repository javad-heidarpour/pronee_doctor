<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\PatientConsentSigningController;
use App\Models\PatientConsent;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class PatientConsentSigningTest extends TestCase
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

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
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

    public function test_owner_patient_can_sign_the_exact_pending_consent_and_record_is_locked(): void
    {
        [$userId, $patientId, $consentId] = $this->seedConsent();
        $this->authenticatePatient($userId);

        $request = Request::create('/my-health/consents/'.$consentId.'/sign', 'POST', [
            'signer_name' => 'مریم رضایی',
            'accept' => 'yes',
        ], [], [], ['HTTP_USER_AGENT' => 'Consent test agent']);

        $response = app(PatientConsentSigningController::class)->sign(
            $request,
            $consentId,
            app(RecordSecurityAuditEventAction::class)
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertDatabaseHas('patient_consents', [
            'id' => $consentId,
            'patient_id' => $patientId,
            'status' => 'signed',
            'signed_by_user_id' => $userId,
            'signer_name' => 'مریم رضایی',
            'signature_method' => 'authenticated_typed_name',
        ]);

        $signed = PatientConsent::query()->findOrFail($consentId);
        $this->assertSame(64, strlen((string) $signed->signature_sha256));
        $this->assertNotNull($signed->signed_at);

        try {
            $signed->update(['consent_text' => 'Tampered consent text']);
            $this->fail('A signed consent must be immutable.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('consent', $exception->errors());
        }
    }

    public function test_owner_signature_rejects_a_name_that_does_not_match_the_patient_record(): void
    {
        [$userId, , $consentId] = $this->seedConsent();
        $this->authenticatePatient($userId);

        try {
            app(PatientConsentSigningController::class)->sign(
                Request::create('/my-health/consents/'.$consentId.'/sign', 'POST', [
                    'signer_name' => 'فرد دیگر',
                    'accept' => 'yes',
                ]),
                $consentId,
                app(RecordSecurityAuditEventAction::class)
            );
            $this->fail('A non-matching signer name must be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }

        $this->assertDatabaseHas('patient_consents', [
            'id' => $consentId,
            'status' => 'pending',
            'signed_by_user_id' => null,
        ]);
    }

    public function test_caregiver_or_revoked_owner_cannot_sign_patient_consent(): void
    {
        [$userId, , $consentId] = $this->seedConsent();
        DB::table('patient_account_accesses')->where('user_id', $userId)->update([
            'access_type' => 'caregiver',
        ]);
        $this->authenticatePatient($userId);

        try {
            app(PatientConsentSigningController::class)->sign(
                Request::create('/my-health/consents/'.$consentId.'/sign', 'POST', [
                    'signer_name' => 'مریم رضایی',
                    'accept' => 'yes',
                ]),
                $consentId,
                app(RecordSecurityAuditEventAction::class)
            );
            $this->fail('Caregivers cannot sign the patient consent.');
        } catch (ModelNotFoundException $exception) {
            $this->assertInstanceOf(ModelNotFoundException::class, $exception);
        }

        $this->assertDatabaseHas('patient_consents', [
            'id' => $consentId,
            'status' => 'pending',
        ]);
    }

    public function test_signing_rejects_consent_text_that_no_longer_matches_its_recorded_hash(): void
    {
        [$userId, , $consentId] = $this->seedConsent();
        $this->authenticatePatient($userId);

        DB::table('patient_consents')->where('id', $consentId)->update([
            'consent_text' => 'متن تغییر داده شده پس از ثبت درخواست رضایت‌نامه.',
        ]);

        try {
            app(PatientConsentSigningController::class)->sign(
                Request::create('/my-health/consents/'.$consentId.'/sign', 'POST', [
                    'signer_name' => 'مریم رضایی',
                    'accept' => 'yes',
                ]),
                $consentId,
                app(RecordSecurityAuditEventAction::class)
            );
            $this->fail('A changed consent body must not be signed against the original content hash.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }

        $this->assertDatabaseHas('patient_consents', [
            'id' => $consentId,
            'status' => 'pending',
            'signed_by_user_id' => null,
        ]);
    }

    private function seedConsent(): array
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Patient',
            'role' => 'user',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $patientId = DB::table('patients')->insertGetId([
            'doctor_id' => 10,
            'name' => 'مریم رضایی',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('patient_account_accesses')->insert([
            'patient_id' => $patientId,
            'user_id' => $userId,
            'access_type' => 'owner',
            'permissions' => json_encode(['view_summary', 'view_records']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $text = 'این رضایت‌نامه برای آزمون امضای الکترونیکی با متن ثابت است.';
        $consentId = DB::table('patient_consents')->insertGetId([
            'patient_id' => $patientId,
            'doctor_id' => 10,
            'clinic_id' => 20,
            'consent_type' => 'treatment',
            'title' => 'رضایت اقدام درمانی',
            'consent_text' => $text,
            'content_sha256' => hash('sha256', $text),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$userId, $patientId, $consentId];
    }

    private function authenticatePatient(int $userId): void
    {
        Auth::guard('web')->setUser(User::query()->findOrFail($userId));
    }
}
