<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\ClinicIntelligenceController;
use App\Models\User;
use App\Services\ClinicIntelligence\InventoryDemandForecaster;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Response;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class ClinicIntelligenceClinicScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
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
            $table->timestamp('deleted_at')->nullable();
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
            $table->timestamps();
        });

        Schema::create('clinic_health_programs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->string('title');
            $table->string('category');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('interval_days')->default(365);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('clinic_health_memberships', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('program_id');
            $table->string('status');
            $table->timestamp('consent_at')->nullable();
            $table->unsignedBigInteger('consent_recorded_by')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('last_checkup_at')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_health_points', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('membership_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedSmallInteger('points');
            $table->string('activity_key');
            $table->string('description');
            $table->string('source_key');
            $table->unsignedBigInteger('awarded_by');
            $table->timestamps();
            $table->unique(['membership_id', 'source_key']);
        });

        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('amount_rial')->default(0);
            $table->string('status');
            $table->timestamp('paid_at')->nullable();
        });

        Schema::create('clinic_expenses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->date('expense_date');
            $table->unsignedBigInteger('amount')->default(0);
        });

        Schema::create('clinic_organizations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_user_id');
            $table->string('status');
        });

        Schema::create('clinic_staff_payrolls', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('status');
            $table->date('period_end')->nullable();
            $table->unsignedBigInteger('net_amount')->default(0);
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'clinic_staff_payrolls',
            'clinic_organizations',
            'clinic_expenses',
            'appointment_payments',
            'clinic_health_points',
            'clinic_health_memberships',
            'clinic_health_programs',
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

    public function test_secretary_dashboard_only_exposes_health_programs_and_memberships_for_assigned_clinic(): void
    {
        $this->authenticateSecretary(['patients.view', 'patients.clinical.view']);

        $assignedPatient = $this->createPatient(41, 'Assigned patient');
        $otherClinicPatient = $this->createPatient(42, 'Other clinic patient');
        $misattributedPatient = $this->createPatient(43, 'Misattributed patient');

        $this->createAppointment($assignedPatient->id, 31);
        $this->createAppointment($otherClinicPatient->id, 32);
        $this->createAppointment($misattributedPatient->id, 32);

        $assignedProgram = $this->createProgram(31, 'Assigned clinic program');
        $otherProgram = $this->createProgram(32, 'Other clinic program');
        $doctorWideProgram = $this->createProgram(null, 'Doctor-wide program');

        $assignedMembership = $this->createMembership($assignedPatient->id, $assignedProgram);
        $otherMembership = $this->createMembership($otherClinicPatient->id, $otherProgram);
        $misattributedMembership = $this->createMembership($misattributedPatient->id, $assignedProgram);
        $doctorWideMembership = $this->createMembership($assignedPatient->id, $doctorWideProgram);

        DB::table('clinic_health_points')->insert([
            'membership_id' => $assignedMembership,
            'doctor_id' => 11,
            'patient_id' => $assignedPatient->id,
            'points' => 5,
            'activity_key' => 'education_completed',
            'description' => 'Education completed',
            'source_key' => 'assigned-event',
            'awarded_by' => 101,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('clinic.intelligence.viewed', null, ['doctor_id' => 11, 'clinic_count' => 1]);

        $request = Request::create('/portal/intelligence', 'GET');
        $request->headers->set('X-Inertia', 'true');

        $response = (new ClinicIntelligenceController())->index(
            $request,
            Mockery::mock(InventoryDemandForecaster::class),
            $audit
        );

        $this->assertInstanceOf(Response::class, $response);
        $props = $response->toResponse($request)->getData(true)['props'];

        $this->assertSame(
            [$assignedProgram],
            collect($props['programs'])->pluck('id')->map(fn ($id) => (int) $id)->all()
        );
        $this->assertSame(
            [$assignedMembership],
            collect($props['memberships'])->pluck('id')->map(fn ($id) => (int) $id)->all()
        );
        $this->assertSame(
            [$assignedMembership],
            collect($props['reminders'])->pluck('membership_id')->map(fn ($id) => (int) $id)->all()
        );
        $this->assertSame(
            [$assignedPatient->id],
            collect($props['patients'])->pluck('id')->map(fn ($id) => (int) $id)->all()
        );
        $this->assertFalse($props['permissions']['health_manage']);
        $this->assertNotContains($otherMembership, collect($props['memberships'])->pluck('id')->all());
        $this->assertNotContains($misattributedMembership, collect($props['memberships'])->pluck('id')->all());
        $this->assertNotContains($doctorWideMembership, collect($props['memberships'])->pluck('id')->all());
        $this->assertNotContains($otherProgram, collect($props['programs'])->pluck('id')->all());
    }

    public function test_patient_view_permission_without_clinical_view_does_not_open_health_dashboard(): void
    {
        $this->authenticateSecretary(['patients.view']);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        $this->assertHttpStatus(fn () => (new ClinicIntelligenceController())->index(
            Request::create('/portal/intelligence', 'GET'),
            Mockery::mock(InventoryDemandForecaster::class),
            $audit
        ), 403);
    }

    public function test_secretary_cannot_enroll_patient_in_unassigned_or_doctor_wide_program(): void
    {
        $this->authenticateSecretary(['patients.manage', 'patients.clinical.manage']);
        $patient = $this->createPatient(42, 'Other clinic patient');
        $this->createAppointment($patient->id, 32);

        $otherProgram = $this->createProgram(32, 'Other clinic program');
        $doctorWideProgram = $this->createProgram(null, 'Doctor-wide program');

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        foreach ([$otherProgram, $doctorWideProgram] as $programId) {
            $this->assertHttpStatus(fn () => (new ClinicIntelligenceController())->enrollPatient(
                Request::create('/portal/intelligence/health/enroll', 'POST', [
                    'patient_id' => $patient->id,
                    'program_id' => $programId,
                    'consent' => true,
                ]),
                $audit
            ), 404);
        }

        $this->assertSame(0, DB::table('clinic_health_memberships')->count());
    }

    public function test_secretary_cannot_check_withdraw_or_award_points_for_another_clinic_membership(): void
    {
        $this->authenticateSecretary(['patients.manage', 'patients.clinical.manage']);
        $patient = $this->createPatient(42, 'Other clinic patient');
        $this->createAppointment($patient->id, 32);
        $program = $this->createProgram(32, 'Other clinic program');
        $membership = $this->createMembership($patient->id, $program);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');
        $controller = new ClinicIntelligenceController();

        $this->assertHttpStatus(fn () => $controller->recordPreventiveCheckup($membership, $audit), 404);
        $this->assertHttpStatus(fn () => $controller->withdrawMembership($membership, $audit), 404);
        $this->assertHttpStatus(fn () => $controller->awardWellnessPoints(
            Request::create('/portal/intelligence/health/points', 'POST', [
                'activity_key' => 'education_completed',
                'description' => 'Education completed',
                'source_key' => 'outside-clinic-event',
            ]),
            $membership,
            $audit
        ), 404);

        $row = DB::table('clinic_health_memberships')->where('id', $membership)->first();
        $this->assertSame('active', $row->status);
        $this->assertNull($row->last_checkup_at);
        $this->assertSame(0, DB::table('clinic_health_points')->count());
    }

    public function test_view_only_secretary_cannot_mutate_health_memberships(): void
    {
        $this->authenticateSecretary(['patients.view', 'patients.clinical.view']);
        $patient = $this->createPatient(41, 'Assigned patient');
        $this->createAppointment($patient->id, 31);
        $program = $this->createProgram(31, 'Assigned clinic program');
        $membership = $this->createMembership($patient->id, $program);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        $this->assertHttpStatus(
            fn () => (new ClinicIntelligenceController())->recordPreventiveCheckup($membership, $audit),
            403
        );

        $this->assertNull(
            DB::table('clinic_health_memberships')->where('id', $membership)->value('last_checkup_at')
        );
    }

    public function test_clinical_manager_can_update_membership_in_assigned_clinic(): void
    {
        $this->authenticateSecretary(['patients.manage', 'patients.clinical.manage']);
        $patient = $this->createPatient(41, 'Assigned patient');
        $this->createAppointment($patient->id, 31);
        $program = $this->createProgram(31, 'Assigned clinic program');
        $membership = $this->createMembership($patient->id, $program);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('clinic.health_membership.checkup_recorded', null, ['doctor_id' => 11, 'status_changed' => true]);
        $audit->shouldReceive('handle')
            ->once()
            ->with('clinic.health_membership.points_awarded', null, ['doctor_id' => 11, 'status_changed' => true]);

        $controller = new ClinicIntelligenceController();
        $controller->recordPreventiveCheckup($membership, $audit);
        $controller->awardWellnessPoints(
            Request::create('/portal/intelligence/health/points', 'POST', [
                'activity_key' => 'education_completed',
                'description' => 'Education completed',
                'source_key' => 'assigned-clinic-event',
            ]),
            $membership,
            $audit
        );

        $this->assertNotNull(
            DB::table('clinic_health_memberships')->where('id', $membership)->value('last_checkup_at')
        );
        $this->assertDatabaseHas('clinic_health_points', [
            'membership_id' => $membership,
            'doctor_id' => 11,
            'patient_id' => $patient->id,
            'source_key' => 'assigned-clinic-event',
            'points' => 5,
        ]);
    }

    public function test_doctor_can_enroll_patient_in_doctor_wide_program(): void
    {
        $this->authenticateDoctor();
        $patient = $this->createPatient(41, 'Doctor patient');
        $this->createAppointment($patient->id, 31);
        $program = $this->createProgram(null, 'Doctor-wide program');

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('clinic.health_membership.enrolled', null, ['doctor_id' => 11, 'clinic_count' => 2]);

        (new ClinicIntelligenceController())->enrollPatient(
            Request::create('/portal/intelligence/health/enroll', 'POST', [
                'patient_id' => $patient->id,
                'program_id' => $program,
                'consent' => true,
            ]),
            $audit
        );

        $this->assertDatabaseHas('clinic_health_memberships', [
            'doctor_id' => 11,
            'patient_id' => $patient->id,
            'program_id' => $program,
            'status' => 'active',
            'consent_recorded_by' => 99,
        ]);
    }

    private function authenticateSecretary(array $permissions): void
    {
        $this->createUsersAndDoctor();

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

    private function authenticateDoctor(): void
    {
        $this->createUsersAndDoctor();
        Auth::guard('web')->setUser(User::query()->findOrFail(99));
        session(['active_doctor_id' => 11]);
    }

    private function createUsersAndDoctor(): void
    {
        DB::table('users')->insert([
            [
                'id' => 99,
                'name' => 'Test Doctor',
                'email' => 'doctor@example.test',
                'password' => 'test',
                'role' => 'doctor',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 101,
                'name' => 'Test Secretary',
                'email' => 'secretary@example.test',
                'password' => 'test',
                'role' => 'secretary',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('doctors')->insert([
            'id' => 11,
            'user_id' => 99,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('addresses')->insert([
            [
                'id' => 31,
                'doctor_id' => 11,
                'name' => 'Assigned clinic',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 32,
                'doctor_id' => 11,
                'name' => 'Other clinic',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function createPatient(int $id, string $name): object
    {
        DB::table('patients')->insert([
            'id' => $id,
            'doctor_id' => 11,
            'name' => $name,
            'phone' => '09120000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (object) ['id' => $id];
    }

    private function createAppointment(int $patientId, int $clinicId): void
    {
        DB::table('appointments')->insert([
            'doctor_id' => 11,
            'patient_id' => $patientId,
            'clinic_id' => $clinicId,
            'status' => 'confirmed',
            'appointment_date' => today(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createProgram(?int $clinicId, string $title): int
    {
        return (int) DB::table('clinic_health_programs')->insertGetId([
            'doctor_id' => 11,
            'clinic_id' => $clinicId,
            'title' => $title,
            'category' => 'general_checkup',
            'description' => null,
            'interval_days' => 365,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createMembership(int $patientId, int $programId): int
    {
        return (int) DB::table('clinic_health_memberships')->insertGetId([
            'doctor_id' => 11,
            'patient_id' => $patientId,
            'program_id' => $programId,
            'status' => 'active',
            'consent_at' => now(),
            'consent_recorded_by' => 101,
            'enrolled_at' => now()->subDays(10),
            'last_checkup_at' => null,
            'created_at' => now()->subDays(10),
            'updated_at' => now(),
        ]);
    }

    private function assertHttpStatus(callable $callback, int $status): void
    {
        try {
            $callback();
            $this->fail('The request should have been rejected.');
        } catch (HttpException $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }
}
