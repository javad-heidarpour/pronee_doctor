<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\PatientCarePathwayController;
use App\Models\User;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\PatientCarePathway;
use App\Models\PatientCarePathwayStep;
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

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('family')->nullable();
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
            $table->softDeletes();
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
        Schema::dropIfExists('patient_care_pathway_steps');\n        Schema::dropIfExists('patient_account_accesses');
        Schema::dropIfExists('patient_care_pathways');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('patients');
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

    public function test_a_patient_can_report_only_a_pending_step_once_for_their_owned_pathway(): void
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

        $response = (new PatientHealthController())->reportCarePathwayStep(
            Request::create('/my-health/pathways/'.$pathway->id.'/steps/'.$step->id.'/complete', 'POST'),
            $pathway->id,
            $step->id,
            $audit
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('patient_reported', $step->fresh()->status);
        $this->assertNotNull($step->fresh()->patient_reported_at);

        try {
            (new PatientHealthController())->reportCarePathwayStep(
                Request::create('/my-health/pathways/'.$pathway->id.'/steps/'.$step->id.'/complete', 'POST'),
                $pathway->id,
                $step->id,
                $audit
            );
            $this->fail('A patient must not report the same step more than once.');
        } catch (\\Symfony\\Component\\HttpKernel\\Exception\\HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertSame('patient_reported', $step->fresh()->status);
        }
    }

    public function test_verifying_a_reported_step_logs_the_pathway_id_after_the_transaction(): void
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
            ->once()
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
}
