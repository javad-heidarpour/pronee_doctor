<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\MedicalNetworkController;
use App\Models\MedicalReferral;
use App\Models\MedicalReferralEvent;
use App\Models\MedicalNetworkProvider;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

final class MedicalNetworkReferralTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('family')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name')->nullable();
            $table->string('family')->nullable();
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
            $table->string('status')->default('confirmed');
            $table->timestamps();
        });

        Schema::create('medical_network_providers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('created_by_user_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->string('provider_type', 24);
            $table->string('name', 180);
            $table->string('slug', 210);
            $table->string('city', 100)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('phone', 45)->nullable();
            $table->string('email')->nullable();
            $table->string('license_number', 100)->nullable();
            $table->json('specialties')->nullable();
            $table->json('services')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 24)->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('completed_referrals_count')->default(0);
            $table->timestamps();
        });

        Schema::create('medical_referrals', function (Blueprint $table): void {
            $table->id();
            $table->string('tracking_code', 20)->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('referring_doctor_id');
            $table->unsignedBigInteger('target_provider_id');
            $table->unsignedBigInteger('created_by_user_id');
            $table->string('service_type', 32);
            $table->json('requested_services')->nullable();
            $table->string('priority', 16)->default('normal');
            $table->string('status', 24)->default('sent');
            $table->string('reason', 240);
            $table->text('clinical_summary')->nullable();
            $table->text('patient_message')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('provider_response')->nullable();
            $table->text('result_summary')->nullable();
            $table->text('patient_instructions')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('source_clinic_id')->nullable();
            $table->timestamps();
        });

        Schema::create('medical_referral_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('medical_referral_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event', 40);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24)->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'medical_referral_events',
            'medical_referrals',
            'medical_network_providers',
            'appointments',
            'patients',
            'addresses',
            'doctors',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_laboratory_referral_requires_and_persists_structured_tests_and_patient_consent(): void
    {
        [$doctor, $patient, $provider] = $this->seedReferralContext();

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('medical_network.referral_created', Mockery::type(MedicalReferral::class), [
                'doctor_id' => $doctor,
                'changed_fields' => ['referral_created'],
            ]);

        $response = (new MedicalNetworkController())->storeReferral(
            Request::create('/portal/medical-network/referrals', 'POST', [
                'patient_id' => $patient,
                'source_clinic_id' => 31,
                'target_provider_id' => $provider,
                'service_type' => 'laboratory',
                'requested_services' => ['CBC', 'FBS', 'Lipid profile'],
                'priority' => 'normal',
                'reason' => 'بررسی آزمایشگاهی',
                'clinical_summary' => 'اطلاعات بالینی ضروری',
                'consent_confirmed' => true,
            ]),
            $audit
        );

        $this->assertSame(302, $response->getStatusCode());
        $referral = MedicalReferral::query()->firstOrFail();
        $this->assertSame(['CBC', 'FBS', 'Lipid profile'], $referral->requested_services);
        $this->assertSame('sent', $referral->status);
        $this->assertNotNull($referral->consent_at);
        $this->assertDatabaseHas('medical_referral_events', [
            'medical_referral_id' => $referral->id,
            'event' => 'created',
            'to_status' => 'sent',
        ]);
    }

    public function test_laboratory_and_imaging_referrals_reject_missing_requested_services(): void
    {
        [$doctor, $patient, $provider] = $this->seedReferralContext();
        $this->authenticateDoctor($doctor);

        foreach (['laboratory', 'imaging'] as $serviceType) {
            try {
                (new MedicalNetworkController())->storeReferral(
                    Request::create('/portal/medical-network/referrals', 'POST', [
                        'patient_id' => $patient,
                        'source_clinic_id' => 31,
                        'target_provider_id' => $provider,
                        'service_type' => $serviceType,
                        'priority' => 'normal',
                        'reason' => 'درخواست تشخیصی',
                        'consent_confirmed' => true,
                    ]),
                    Mockery::mock(RecordSecurityAuditEventAction::class)
                );
                $this->fail('Diagnostic referrals must include requested services.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('requested_services', $exception->errors());
            }
        }
    }

    public function test_diagnostic_request_rejects_duplicate_and_excessive_service_items(): void
    {
        [$doctor, $patient, $provider] = $this->seedReferralContext();

        foreach ([array_fill(0, 31, 'CBC'), ['CBC', 'CBC']] as $items) {
            try {
                (new MedicalNetworkController())->storeReferral(
                    Request::create('/portal/medical-network/referrals', 'POST', [
                        'patient_id' => $patient,
                        'source_clinic_id' => 31,
                        'target_provider_id' => $provider,
                        'service_type' => 'laboratory',
                        'requested_services' => $items,
                        'priority' => 'normal',
                        'reason' => 'درخواست تشخیصی',
                        'consent_confirmed' => true,
                    ]),
                    Mockery::mock(RecordSecurityAuditEventAction::class)
                );
                $this->fail('Duplicate or excessive diagnostic items must be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('requested_services', $exception->errors());
            }
        }
    }

    public function test_provider_must_explain_a_referral_rejection(): void
    {
        [$doctor, $patient, $provider] = $this->seedReferralContext();
        $referral = MedicalReferral::query()->create([
            'tracking_code' => 'REF-TEST-002',
            'patient_id' => $patient,
            'referring_doctor_id' => $doctor,
            'target_provider_id' => $provider,
            'created_by_user_id' => 99,
            'source_clinic_id' => 31,
            'service_type' => 'laboratory',
            'requested_services' => ['CBC'],
            'priority' => 'normal',
            'status' => 'sent',
            'reason' => 'درخواست آزمایش',
            'consent_at' => now(),
        ]);

        $this->authenticateDoctor(200);

        $this->expectException(ValidationException::class);

        (new MedicalNetworkController())->updateReferral(
            Request::create('/portal/medical-network/referrals/'.$referral->id, 'PATCH', [
                'status' => 'rejected',
            ]),
            $referral,
            app(\\App\\Services\\MedicalReferralWorkflow::class),
            Mockery::mock(RecordSecurityAuditEventAction::class)
        );
    }

    public function test_referral_rejects_a_patient_without_an_appointment_at_the_source_clinic(): void
    {
        [$doctor, $patient, $provider] = $this->seedReferralContext();
        DB::table('appointments')->where('patient_id', $patient)->update(['clinic_id' => 32]);

        $this->authenticateDoctor($doctor);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        (new MedicalNetworkController())->storeReferral(
            Request::create('/portal/medical-network/referrals', 'POST', [
                'patient_id' => $patient,
                'source_clinic_id' => 31,
                'target_provider_id' => $provider,
                'service_type' => 'laboratory',
                'requested_services' => ['CBC'],
                'priority' => 'normal',
                'reason' => 'درخواست تشخیصی',
                'consent_confirmed' => true,
            ]),
            Mockery::mock(RecordSecurityAuditEventAction::class)
        );
    }

    public function test_only_the_recipient_can_complete_a_referral_and_must_record_a_result(): void
    {
        [$doctor, $patient, $provider] = $this->seedReferralContext();
        $referral = MedicalReferral::query()->create([
            'tracking_code' => 'REF-TEST-001',
            'patient_id' => $patient,
            'referring_doctor_id' => $doctor,
            'target_provider_id' => $provider,
            'created_by_user_id' => 99,
            'source_clinic_id' => 31,
            'service_type' => 'imaging',
            'requested_services' => ['سونوگرافی شکم'],
            'priority' => 'normal',
            'status' => 'in_progress',
            'reason' => 'بررسی تشخیصی',
            'consent_at' => now(),
        ]);

        $this->authenticateDoctor(200);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('medical_network.referral_status_changed', Mockery::type(MedicalReferral::class), [
                'doctor_id' => 200,
                'status_changed' => true,
                'changed_fields' => ['status'],
            ]);

        $response = (new MedicalNetworkController())->updateReferral(
            Request::create('/portal/medical-network/referrals/'.$referral->id, 'PATCH', [
                'status' => 'completed',
                'result_summary' => 'نتیجه تصویربرداری ثبت شد.',
                'patient_instructions' => 'برای پیگیری به پزشک ارجاع‌دهنده مراجعه شود.',
            ]),
            $referral,
            app(\App\Services\MedicalReferralWorkflow::class),
            $audit
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('completed', $referral->fresh()->status);
        $this->assertSame('نتیجه تصویربرداری ثبت شد.', $referral->fresh()->result_summary);
        $this->assertSame(1, $providerModel = MedicalNetworkProvider::query()->findOrFail($provider)->completed_referrals_count);
    }

    private function seedReferralContext(): array
    {
        DB::table('doctors')->insert([
            'id' => 11,
            'user_id' => 99,
            'name' => 'Referring',
            'family' => 'Doctor',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('doctors')->insert([
            'id' => 22,
            'user_id' => 200,
            'name' => 'Receiving',
            'family' => 'Doctor',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('addresses')->insert([
            'id' => 31,
            'doctor_id' => 11,
            'name' => 'Source Clinic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $patient = DB::table('patients')->insertGetId([
            'doctor_id' => 11,
            'name' => 'Test Patient',
            'phone' => '09120000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('appointments')->insert([
            'doctor_id' => 11,
            'patient_id' => $patient,
            'clinic_id' => 31,
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $provider = DB::table('medical_network_providers')->insertGetId([
            'created_by_user_id' => 200,
            'doctor_id' => 22,
            'provider_type' => 'laboratory',
            'name' => 'Test Lab',
            'slug' => 'test-lab',
            'status' => 'active',
            'is_verified' => true,
            'completed_referrals_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->authenticateDoctor(99);

        return [11, $patient, $provider];
    }

    private function authenticateDoctor(int $userId): void
    {
        $user = new User();
        $user->id = $userId;
        $user->role = 'doctor';
        $user->is_active = true;
        Auth::guard('web')->setUser($user);
        session(['active_doctor_id' => 11]);
    }
}
