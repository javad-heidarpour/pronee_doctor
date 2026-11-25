<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\PatientCarePathwayController;
use App\Http\Controllers\PatientHealthController;
use App\Models\User;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\PatientCarePathway;
use App\Models\PatientCarePathwayStep;
use App\Models\PatientAccountAccess;
use App\Services\PatientCarePathwayProgress;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

final class PatientCarePathwayVerificationTest extends TestCase
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

        Schema::create('doctor_user', function (Blueprint $table): void {
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('user_id');
            $table->json('permissions')->nullable();
            $table->json('clinic_ids')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('family')->nullable();
            $table->string('role')->nullable();
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
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('patient_account_accesses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('granted_by_user_id')->nullable();
            $table->string('access_type');
            $table->json('permissions')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_care_pathways', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('goal')->nullable();
            $table->string('status');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_care_pathway_steps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pathway_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category');
            $table->dateTime('due_at')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status')->default('pending');
            $table->dateTime('patient_reported_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->text('verification_note')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('patient_care_pathway_steps');
        Schema::dropIfExists('patient_account_accesses');
        Schema::dropIfExists('patient_care_pathways');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('doctor_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('doctors');

        parent::tearDown();
    }

    public function test_pathway_list_access_is_audited_without_patient_or_step_values(): void
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
            'name' => 'Test Clinic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $patient = Patient::query()->create([
            'doctor_id' => 11,
            'name' => 'Patient',
        ]);
        Appointment::query()->create([
            'doctor_id' => 11,
            'patient_id' => $patient->id,
            'clinic_id' => 31,
            'status' => 'confirmed',
        ]);
        PatientCarePathway::query()->create([
            'doctor_id' => 11,
            'clinic_id' => 31,
            'patient_id' => $patient->id,
            'title' => 'Care plan',
            'status' => 'active',
        ]);

        $user = new User();
        $user->id = 99;
        $user->role = 'doctor';
        Auth::guard('web')->setUser($user);
        session(['active_doctor_id' => 11]);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('patient.care_pathways.listed', null, ['doctor_id' => 11]);

        $response = (new PatientCarePathwayController())->index($audit);

        $this->assertInstanceOf(\Inertia\Response::class, $response);
    }

    public function test_verifying_reported_steps_is_serialized_and_completes_the_pathway(): void
    {
        $pathway = PatientCarePathway::query()->create([
            'doctor_id' => 11,
            'clinic_id' => 31,
            'patient_id' => 41,
            'title' => 'Follow-up plan',
            'status' => 'active',
        ]);

        $step = $pathway->steps()->create([
            'title' => 'Measure blood pressure',
            'category' => 'measurement',
            'sort_order' => 1,
            'status' => 'patient_reported',
        ]);

        $secondStep = $pathway->steps()->create([
            'title' => 'Attend follow-up appointment',
            'category' => 'appointment',
            'sort_order' => 2,
            'status' => 'patient_reported',
        ]);

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
            'name' => 'Test Clinic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = new User();
        $user->id = 99;
        $user->role = 'doctor';
        Auth::guard('web')->setUser($user);
        session(['active_doctor_id' => 11]);

        $controller = new PatientCarePathwayController();

        // Resolve the real service used by the controller; do not mask missing imports.
        $this->assertInstanceOf(PatientCarePathwayProgress::class, app(PatientCarePathwayProgress::class));

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->twice()
            ->with(
                'patient.care_pathway.step_verified',
                Mockery::type(PatientCarePathwayStep::class),
                ['pathway_id' => $pathway->id]
            );

        $response = $controller->verifyStep(
            Request::create('/portal/care-pathway-steps/'.$step->id.'/verify', 'POST'),
            $step->id,
            $audit
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('verified', $step->fresh()->status);
        $this->assertSame('active', $pathway->fresh()->status);

        $secondResponse = $controller->verifyStep(
            Request::create('/portal/care-pathway-steps/'.$secondStep->id.'/verify', 'POST'),
            $secondStep->id,
            $audit
        );

        $this->assertSame(302, $secondResponse->getStatusCode());
        $this->assertSame('verified', $secondStep->fresh()->status);
        $this->assertSame('completed', $pathway->fresh()->status);
    }

    public function test_secretary_with_patient_management_only_cannot_create_clinical_care_pathway(): void
    {
        $this->seedDoctorAndClinic();
        $patient = Patient::query()->create(['doctor_id' => 11, 'name' => 'Patient']);
        Appointment::query()->create([
            'doctor_id' => 11,
            'patient_id' => $patient->id,
            'clinic_id' => 31,
            'status' => 'confirmed',
        ]);
        $this->authenticateSecretary(['patients.manage']);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        $request = Request::create('/portal/care-pathways', 'POST', [
            'patient_id' => $patient->id,
            'clinic_id' => 31,
            'title' => 'Restricted clinical plan',
            'steps' => [[
                'title' => 'Clinical action',
                'category' => 'general',
            ]],
        ]);

        try {
            (new PatientCarePathwayController())->store($request, $audit);
            $this->fail('A secretary without clinical write permission must be denied.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame(0, PatientCarePathway::query()->count());
    }

    public function test_secretary_with_patient_management_only_cannot_verify_a_clinical_step(): void
    {
        $this->seedDoctorAndClinic();
        $pathway = PatientCarePathway::query()->create([
            'doctor_id' => 11,
            'clinic_id' => 31,
            'patient_id' => 41,
            'title' => 'Restricted pathway',
            'status' => 'active',
        ]);
        $step = $pathway->steps()->create([
            'title' => 'Reported clinical action',
            'category' => 'measurement',
            'status' => 'patient_reported',
        ]);
        $this->authenticateSecretary(['patients.manage']);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        try {
            (new PatientCarePathwayController())->verifyStep(
                Request::create('/portal/care-pathway-steps/'.$step->id.'/verify', 'POST'),
                $step->id,
                $audit
            );
            $this->fail('A secretary without clinical write permission must be denied.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame('patient_reported', $step->fresh()->status);
        $this->assertNull($step->fresh()->verified_at);
    }

    public function test_secretary_with_clinical_view_but_without_write_permission_gets_read_only_page(): void
    {
        $this->seedDoctorAndClinic();
        $patient = Patient::query()->create(['doctor_id' => 11, 'name' => 'Visible patient']);
        Appointment::query()->create([
            'doctor_id' => 11,
            'patient_id' => $patient->id,
            'clinic_id' => 31,
            'status' => 'confirmed',
        ]);
        PatientCarePathway::query()->create([
            'doctor_id' => 11,
            'clinic_id' => 31,
            'patient_id' => $patient->id,
            'title' => 'Visible plan',
            'status' => 'active',
        ]);
        $this->authenticateSecretary(['patients.clinical.view']);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('patient.care_pathways.listed', null, ['doctor_id' => 11]);

        $request = Request::create('/portal/care-pathways', 'GET');
        $request->headers->set('X-Inertia', 'true');
        $response = (new PatientCarePathwayController())->index($audit);
        $props = $response->toResponse($request)->getData(true)['props'];

        $this->assertFalse($props['canManageClinical']);
        $this->assertCount(1, $props['pathways']['data']);
    }


    public function test_secretary_with_clinical_write_permission_can_manage_but_cannot_activate_pathways(): void
    {
        $this->seedDoctorAndClinic();
        $patient = Patient::query()->create(['doctor_id' => 11, 'name' => 'Visible patient']);
        Appointment::query()->create([
            'doctor_id' => 11,
            'patient_id' => $patient->id,
            'clinic_id' => 31,
            'status' => 'confirmed',
        ]);
        PatientCarePathway::query()->create([
            'doctor_id' => 11,
            'clinic_id' => 31,
            'patient_id' => $patient->id,
            'title' => 'Draft plan',
            'status' => 'draft',
        ]);
        $this->authenticateSecretary(['patients.clinical.manage']);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('patient.care_pathways.listed', null, ['doctor_id' => 11]);

        $request = Request::create('/portal/care-pathways', 'GET');
        $request->headers->set('X-Inertia', 'true');
        $response = (new PatientCarePathwayController())->index($audit);
        $props = $response->toResponse($request)->getData(true)['props'];

        $this->assertTrue($props['canManageClinical']);
        $this->assertFalse($props['canActivatePathway']);
    }

    public function test_patient_can_report_only_a_pending_step_once_for_their_owned_pathway(): void
    {
        $patient = Patient::query()->create([
            'doctor_id' => 11,
            'name' => 'Patient owner',
        ]);
        $pathway = PatientCarePathway::query()->create([
            'doctor_id' => 11,
            'clinic_id' => 31,
            'patient_id' => $patient->id,
            'title' => 'Patient care plan',
            'status' => 'active',
        ]);
        $step = $pathway->steps()->create([
            'title' => 'Record blood pressure',
            'category' => 'measurement',
            'sort_order' => 1,
            'status' => 'pending',
        ]);

        PatientAccountAccess::query()->create([
            'patient_id' => $patient->id,
            'user_id' => 101,
            'access_type' => 'owner',
        ]);

        $user = new User();
        $user->id = 101;
        $user->role = 'user';
        $user->is_active = true;
        Auth::guard('web')->setUser($user);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with(
                'patient.care_pathway.step_reported',
                Mockery::type(PatientCarePathwayStep::class),
                ['pathway_id' => $pathway->id]
            );

        $controller = new PatientHealthController();
        $response = $controller->reportCarePathwayStep(
            Request::create('/my-health/pathways/'.$pathway->id.'/steps/'.$step->id.'/complete', 'POST'),
            $pathway->id,
            $step->id,
            $audit
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('patient_reported', $step->fresh()->status);
        $this->assertNotNull($step->fresh()->patient_reported_at);

        try {
            $controller->reportCarePathwayStep(
                Request::create('/my-health/pathways/'.$pathway->id.'/steps/'.$step->id.'/complete', 'POST'),
                $pathway->id,
                $step->id,
                $audit
            );
            $this->fail('A patient must not report the same step more than once.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertSame('patient_reported', $step->fresh()->status);
        }
    }

    public function test_patient_report_is_rolled_back_when_audit_recording_fails(): void
    {
        $patient = Patient::query()->create([
            'doctor_id' => 11,
            'name' => 'Patient owner',
        ]);
        $pathway = PatientCarePathway::query()->create([
            'doctor_id' => 11,
            'clinic_id' => 31,
            'patient_id' => $patient->id,
            'title' => 'Patient care plan',
            'status' => 'active',
        ]);
        $step = $pathway->steps()->create([
            'title' => 'Record blood pressure',
            'category' => 'measurement',
            'sort_order' => 1,
            'status' => 'pending',
        ]);
        PatientAccountAccess::query()->create([
            'patient_id' => $patient->id,
            'user_id' => 101,
            'access_type' => 'owner',
        ]);

        $user = new User();
        $user->id = 101;
        $user->role = 'user';
        $user->is_active = true;
        Auth::guard('web')->setUser($user);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->andThrow(new \RuntimeException('audit unavailable'));

        try {
            (new PatientHealthController())->reportCarePathwayStep(
                Request::create('/my-health/pathways/'.$pathway->id.'/steps/'.$step->id.'/complete', 'POST'),
                $pathway->id,
                $step->id,
                $audit
            );
            $this->fail('A failed audit write must roll back the patient transition.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('audit unavailable', $exception->getMessage());
        }

        $this->assertSame('pending', $step->fresh()->status);
        $this->assertNull($step->fresh()->patient_reported_at);
    }

    public function test_staff_verification_is_rolled_back_when_audit_recording_fails(): void
    {
        $this->seedDoctorAndClinic();
        $pathway = PatientCarePathway::query()->create([
            'doctor_id' => 11,
            'clinic_id' => 31,
            'patient_id' => 41,
            'title' => 'Audited pathway',
            'status' => 'active',
        ]);
        $step = $pathway->steps()->create([
            'title' => 'Reported clinical action',
            'category' => 'measurement',
            'status' => 'patient_reported',
        ]);

        $user = new User();
        $user->id = 99;
        $user->role = 'doctor';
        $user->is_active = true;
        Auth::guard('web')->setUser($user);
        session(['active_doctor_id' => 11]);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->andThrow(new \RuntimeException('audit unavailable'));

        try {
            (new PatientCarePathwayController())->verifyStep(
                Request::create('/portal/care-pathway-steps/'.$step->id.'/verify', 'POST'),
                $step->id,
                $audit
            );
            $this->fail('A failed audit write must roll back staff verification.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('audit unavailable', $exception->getMessage());
        }

        $this->assertSame('patient_reported', $step->fresh()->status);
        $this->assertNull($step->fresh()->verified_at);
        $this->assertSame('active', $pathway->fresh()->status);
    }

    private function seedDoctorAndClinic(): void
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
            'name' => 'Test Clinic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function authenticateSecretary(array $permissions): void
    {
        DB::table('users')->insert([
            'id' => 77,
            'name' => 'Secretary',
            'family' => 'Test',
            'role' => 'secretary',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('doctor_user')->insert([
            'doctor_id' => 11,
            'user_id' => 77,
            'permissions' => json_encode($permissions),
            'clinic_ids' => json_encode([31]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = new User();
        $user->id = 77;
        $user->role = 'secretary';
        $user->is_active = true;
        Auth::guard('web')->setUser($user);
        session(['active_doctor_id' => 11]);
    }

}
