<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\HealthInteropController;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientEncounter;
use App\Models\PatientInsuranceVerification;
use App\Services\HealthInterop\HealthExchangeOutbox;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Response;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class HealthInteropAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('national_code')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('clinic_id');
            $table->string('status');
            $table->date('appointment_date')->nullable();
            $table->string('appointment_time')->nullable();
            $table->timestamps();
        });

        Schema::create('health_exchange_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('encounter_id')->nullable();
            $table->string('destination');
            $table->string('event_type');
            $table->string('idempotency_key')->unique();
            $table->string('status');
            $table->text('payload');
            $table->text('response_payload')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->dateTime('available_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->string('remote_tracking_id')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_insurance_verifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->string('payer_code');
            $table->string('payer_name')->nullable();
            $table->text('policy_number')->nullable();
            $table->text('member_number')->nullable();
            $table->string('status');
            $table->dateTime('checked_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('patient_insurance_verifications');
        Schema::dropIfExists('health_exchange_messages');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('doctors');

        parent::tearDown();
    }

    private function authenticatePortalUser(string $role = 'doctor'): void
    {
        DB::table('doctors')->insert([
            'id' => 11,
            'user_id' => 99,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('addresses')->insert([
            'id' => 31,
            'doctor_id' => 11,
            'name' => 'Test clinic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = new User();
        $user->id = 99;
        $user->role = $role;
        Auth::guard('web')->setUser($user);
        session(['active_doctor_id' => 11]);
    }

    public function test_health_interop_dashboard_access_is_audited_without_insurance_or_exchange_payloads(): void
    {
        $this->authenticatePortalUser('admin');

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('health_interop.dashboard.viewed', null, ['doctor_id' => 11]);

        $response = (new HealthInteropController())->index($audit);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function test_patient_identity_check_is_audited_without_national_code_values(): void
    {
        $this->authenticatePortalUser();

        $patient = new Patient();
        $patient->id = 41;
        $patient->doctor_id = 11;
        $patient->national_code = '0000000000';

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('health_interop.patient_identity.checked', $patient, ['doctor_id' => 11]);

        $response = (new HealthInteropController())->validateIdentity(
            Request::create('/portal/health-interop/patients/41/identity-check', 'POST'),
            $patient,
            $audit
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->getData()->identity_verified);
    }

    public function test_identity_check_rejects_a_patient_owned_by_another_doctor(): void
    {
        $this->authenticatePortalUser();

        $patient = new Patient();
        $patient->id = 42;
        $patient->doctor_id = 12;
        $patient->national_code = '0000000000';

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        try {
            (new HealthInteropController())->validateIdentity(
                Request::create('/portal/health-interop/patients/42/identity-check', 'POST'),
                $patient,
                $audit
            );
            $this->fail('A patient from another doctor must not be checked.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }

    public function test_appointment_export_is_audited_after_the_outbox_accepts_it(): void
    {
        $this->authenticatePortalUser();

        DB::table('patients')->insert([
            'id' => 41,
            'doctor_id' => 11,
            'name' => 'Patient',
            'national_code' => '0000000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('appointments')->insert([
            'id' => 501,
            'doctor_id' => 11,
            'patient_id' => 41,
            'clinic_id' => 31,
            'status' => 'confirmed',
            'appointment_date' => today(),
            'appointment_time' => '10:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $patient = Patient::query()->findOrFail(41);
        $appointment = Appointment::query()->findOrFail(501);
        $appointment->setRelation('patient', $patient);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->once()->with(
            'health_interop.appointment.queued',
            $appointment,
            ['doctor_id' => 11]
        );

        (new HealthInteropController())->queueAppointment(
            $appointment,
            app(HealthExchangeOutbox::class),
            $audit
        );

        $this->assertDatabaseHas('health_exchange_messages', [
            'doctor_id' => 11,
            'appointment_id' => 501,
            'event_type' => 'appointment.booked',
        ]);
    }

    public function test_signed_encounter_export_is_audited_without_clinical_payload_metadata(): void
    {
        $this->authenticatePortalUser();

        DB::table('patients')->insert([
            'id' => 41,
            'doctor_id' => 11,
            'name' => 'Patient',
            'national_code' => '0000000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('appointments')->insert([
            'id' => 501,
            'doctor_id' => 11,
            'patient_id' => 41,
            'clinic_id' => 31,
            'status' => 'visited',
            'appointment_date' => today(),
            'appointment_time' => '10:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $patient = Patient::query()->findOrFail(41);
        $appointment = Appointment::query()->findOrFail(501);
        $appointment->setRelation('patient', $patient);

        $encounter = new PatientEncounter();
        $encounter->id = 601;
        $encounter->doctor_id = 11;
        $encounter->clinic_id = 31;
        $encounter->patient_id = 41;
        $encounter->appointment_id = 501;
        $encounter->status = PatientEncounter::STATUS_SIGNED;
        $encounter->diagnosis = 'Sensitive diagnosis';
        $encounter->plan = 'Sensitive treatment plan';
        $encounter->signed_at = now();
        $encounter->setRelation('patient', $patient);
        $encounter->setRelation('prescriptions', collect());
        $encounter->setRelation('appointment', $appointment);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->once()->with(
            'health_interop.encounter.queued',
            $encounter,
            ['doctor_id' => 11]
        );

        (new HealthInteropController())->queueEncounter(
            $encounter,
            app(HealthExchangeOutbox::class),
            $audit
        );

        $this->assertDatabaseHas('health_exchange_messages', [
            'doctor_id' => 11,
            'appointment_id' => 501,
            'encounter_id' => 601,
            'event_type' => 'encounter.signed',
        ]);
    }

    public function test_insurance_verification_creation_is_audited_without_policy_values(): void
    {
        $this->authenticatePortalUser();

        $patient = new Patient();
        $patient->id = 41;
        $patient->doctor_id = 11;

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with(
                'health_interop.insurance_verification.created',
                Mockery::on(fn ($subject): bool => $subject instanceof PatientInsuranceVerification
                    && (int) $subject->patient_id === 41),
                ['doctor_id' => 11]
            );

        $response = (new HealthInteropController())->verifyInsurance(
            Request::create('/portal/health-interop/patients/41/insurance-check', 'POST', [
                'payer_code' => 'tamin',
            ]),
            $patient,
            $audit
        );

        $this->assertSame(202, $response->getStatusCode());
        $this->assertSame('not_configured', $response->getData()->status);
    }
}
