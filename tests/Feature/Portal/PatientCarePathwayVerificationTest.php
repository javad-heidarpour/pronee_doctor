<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\PatientCarePathwayController;
use App\Models\Doctor;
use App\Models\PatientCarePathway;
use App\Models\PatientCarePathwayStep;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

final class PatientCarePathwayVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
        Schema::dropIfExists('patient_care_pathways');

        parent::tearDown();
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

        $controller = new class extends PatientCarePathwayController {
            public Doctor $testDoctor;

            protected function currentDoctor(): Doctor
            {
                return $this->testDoctor;
            }

            protected function authorizeSecretaryPermission(string $permission, ?Doctor $doctor = null): void
            {
            }

            protected function accessibleClinicIds(?Doctor $doctor = null): array
            {
                return [31];
            }
        };
        $controller->testDoctor = new Doctor();
        $controller->testDoctor->id = 11;

        // Keep this regression test focused on the transaction result and audit event.
        app()->instance(
            'App\\Http\\Controllers\\Portal\\PatientCarePathwayProgress',
            new class {
                public function isComplete($steps): bool
                {
                    return false;
                }
            }
        );

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
    }
}
