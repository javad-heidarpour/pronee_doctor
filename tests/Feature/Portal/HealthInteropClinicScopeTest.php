<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\HealthInteropController;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientEncounter;
use App\Models\User;
use App\Services\HealthInterop\HealthExchangeOutbox;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class HealthInteropClinicScopeTest extends TestCase
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

        Schema::create('doctor_user', function (Blueprint $table): void {
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('user_id');
            $table->json('permissions')->nullable();
            $table->json('clinic_ids')->nullable();
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

        Schema::create('patient_encounters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('clinic_id');
            $table->string('status');
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
            $table->timestamp('available_at')->nullable();
            $table->timestamp('sent_at')->nullable();
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
            $table->timestamp('checked_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'patient_insurance_verifications',
            'health_exchange_messages',
            'patient_encounters',
            'appointments',
            'patients',
            'addresses',
            'doctor_user',
            'doctors',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_secretary_cannot_validate_identity_for_patient_seen_only_in_another_clinic(): void
    {
        $this->authenticateSecretary(['patients.clinical.view']);
        $patient = $this->createPatient(41);
        $this->createAppointment(702, 41, 32);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        $this->assertNotFound(fn () => (new HealthInteropController())->validateIdentity(
            Request::create('/portal/health-interop/patients/41/identity-check', 'POST'),
            $patient,
            $audit
        ));
    }

    public function test_secretary_cannot_create_insurance_check_for_an_appointment_outside_assigned_clinic(): void
    {
        $this->authenticateSecretary(['patients.clinical.manage']);
        $patient = $this->createPatient(41);
        $this->createAppointment(701, 41, 31);
        $this->createAppointment(702, 41, 32);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        try {
            (new HealthInteropController())->verifyInsurance(
                Request::create('/portal/health-interop/patients/41/insurance-check', 'POST', [
                    'payer_code' => 'tamin',
                    'appointment_id' => 702,
                ]),
                $patient,
                $audit
            );
            $this->fail('An insurance check must not be attached to another clinic appointment.');
        } catch (ModelNotFoundException) {
            $this->assertSame(0, DB::table('patient_insurance_verifications')->count());
        }
    }

    public function test_secretary_must_attribute_insurance_check_to_an_accessible_appointment(): void
    {
        $this->authenticateSecretary(['patients.clinical.manage']);
        $patient = $this->createPatient(41);
        $this->createAppointment(701, 41, 31);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        try {
            (new HealthInteropController())->verifyInsurance(
                Request::create('/portal/health-interop/patients/41/insurance-check', 'POST', [
                    'payer_code' => 'tamin',
                ]),
                $patient,
                $audit
            );
            $this->fail('A secretary must provide an accessible appointment for clinic attribution.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('appointment_id', $exception->errors());
            $this->assertSame(0, DB::table('patient_insurance_verifications')->count());
        }
    }

    public function test_secretary_cannot_queue_appointments_or_encounters_from_another_clinic(): void
    {
        $this->authenticateSecretary(['appointments.manage', 'patients.clinical.manage']);
        $patient = $this->createPatient(41);
        $appointment = $this->createAppointment(702, 41, 32);
        $encounter = $this->createEncounter(802, 41, 702, 32);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');
        $outbox = new HealthExchangeOutbox();
        $controller = new HealthInteropController();

        $this->assertNotFound(fn () => $controller->queueAppointment($appointment, $outbox, $audit));
        $this->assertNotFound(fn () => $controller->queueEncounter($encounter, $outbox, $audit));

        $this->assertSame(0, DB::table('health_exchange_messages')->count());
    }

    public function test_secretary_cannot_export_an_encounter_with_a_mismatched_appointment_link(): void
    {
        $this->authenticateSecretary(['patients.clinical.manage']);
        $patient = $this->createPatient(41);
        $this->createAppointment(701, 41, 31);
        $this->createAppointment(702, 41, 32);
        $encounter = $this->createEncounter(801, 41, 702, 31);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        $this->assertNotFound(fn () => (new HealthInteropController())->queueEncounter(
            $encounter,
            new HealthExchangeOutbox(),
            $audit
        ));

        $this->assertSame(0, DB::table('health_exchange_messages')->count());
    }

    public function test_secretary_dashboard_only_returns_exchange_and_insurance_records_linked_to_accessible_clinic(): void
    {
        $this->authenticateSecretary(['patients.clinical.view']);
        $this->createPatient(41);
        $this->createAppointment(701, 41, 31);
        $this->createAppointment(702, 41, 32);
        $this->createEncounter(801, 41, 701, 31);
        $this->createEncounter(802, 41, 702, 32);

        $this->createMessage(501, 41, 701, null);
        $this->createMessage(502, 41, 702, null);
        $this->createMessage(503, 41, 701, 801);
        $this->createMessage(504, 41, 702, 802);
        $this->createMessage(505, 41, null, null);
        $this->createMessage(506, 99, 701, null);
        $this->createMessage(507, 41, 702, 801);

        $this->createInsuranceCheck(601, 41, 701);
        $this->createInsuranceCheck(602, 41, 702);
        $this->createInsuranceCheck(603, 99, 701);
        $this->createInsuranceCheck(604, 41, null);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('health_interop.dashboard.viewed', null, ['doctor_id' => 11]);

        $request = Request::create('/portal/health-interop', 'GET');
        $request->headers->set('X-Inertia', 'true');
        $response = (new HealthInteropController())->index($audit);
        $props = $response->toResponse($request)->getData(true)['props'];

        $messageIds = collect($props['messages'])->pluck('id')->map(fn ($id) => (int) $id)->all();
        $insuranceIds = collect($props['insuranceChecks'])->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertEqualsCanonicalizing([501, 503], $messageIds);
        $this->assertSame([601], $insuranceIds);
    }

    private function authenticateSecretary(array $permissions): void
    {
        DB::table('users')->insert([
            'id' => 101,
            'name' => 'Test Secretary',
            'family' => 'User',
            'role' => 'secretary',
            'is_active' => true,
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctors')->insert([
            'id' => 11,
            'user_id' => 99,
            'is_active' => true,
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('addresses')->insert([
            'id' => 31,
            'doctor_id' => 11,
            'name' => 'Assigned clinic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('doctor_user')->insert([
            'doctor_id' => 11,
            'user_id' => 101,
            'permissions' => json_encode($permissions),
            'clinic_ids' => json_encode([31]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::guard('web')->setUser(User::query()->findOrFail(101));
        session(['active_doctor_id' => 11]);
    }

    private function createPatient(int $id): Patient
    {
        DB::table('patients')->insert([
            'id' => $id,
            'doctor_id' => 11,
            'name' => 'Test patient',
            'phone' => '09120000000',
            'national_code' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Patient::query()->findOrFail($id);
    }

    private function createAppointment(int $id, int $patientId, int $clinicId): Appointment
    {
        DB::table('appointments')->insert([
            'id' => $id,
            'doctor_id' => 11,
            'patient_id' => $patientId,
            'clinic_id' => $clinicId,
            'status' => 'confirmed',
            'appointment_date' => today(),
            'appointment_time' => '10:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Appointment::query()->findOrFail($id);
    }

    private function createEncounter(int $id, int $patientId, int $appointmentId, int $clinicId): PatientEncounter
    {
        DB::table('patient_encounters')->insert([
            'id' => $id,
            'doctor_id' => 11,
            'patient_id' => $patientId,
            'appointment_id' => $appointmentId,
            'clinic_id' => $clinicId,
            'status' => PatientEncounter::STATUS_SIGNED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return PatientEncounter::query()->findOrFail($id);
    }

    private function createMessage(int $id, int $patientId, ?int $appointmentId, ?int $encounterId): void
    {
        DB::table('health_exchange_messages')->insert([
            'id' => $id,
            'doctor_id' => 11,
            'patient_id' => $patientId,
            'appointment_id' => $appointmentId,
            'encounter_id' => $encounterId,
            'destination' => 'sepas',
            'event_type' => $encounterId ? 'encounter.signed' : 'appointment.booked',
            'idempotency_key' => hash('sha256', 'message-'.$id),
            'status' => 'pending',
            'payload' => 'encrypted-payload',
            'response_payload' => null,
            'attempts' => 0,
            'available_at' => now(),
            'sent_at' => null,
            'remote_tracking_id' => null,
            'last_error' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createInsuranceCheck(int $id, int $patientId, ?int $appointmentId): void
    {
        DB::table('patient_insurance_verifications')->insert([
            'id' => $id,
            'doctor_id' => 11,
            'patient_id' => $patientId,
            'appointment_id' => $appointmentId,
            'payer_code' => 'tamin',
            'payer_name' => 'Test payer',
            'policy_number' => null,
            'member_number' => null,
            'status' => 'not_configured',
            'checked_at' => null,
            'failure_message' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assertNotFound(callable $callback): void
    {
        try {
            $callback();
            $this->fail('The clinic-scoped operation should return 404.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }
}
