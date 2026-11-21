<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\PatientMedicationController;
use App\Models\PatientMedicationPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class PatientMedicationPlanStateTest extends TestCase
{
    private User $user;

    private string $scheduledFor;

    protected function setUp(): void
    {
        parent::setUp();

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

        Schema::create('patient_medication_plans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('prescription_id');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->json('reminder_times');
            $table->string('timezone', 64);
            $table->string('status', 16);
            $table->timestamps();
        });

        Schema::create('patient_medication_doses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('plan_id');
            $table->dateTime('scheduled_for');
            $table->string('status', 16);
            $table->unsignedBigInteger('recorded_by_user_id');
            $table->dateTime('recorded_at');
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->unique(['plan_id', 'scheduled_for']);
        });

        $this->user = new User();
        $this->user->id = 99;
        $this->user->role = 'user';
        $this->user->is_active = true;
        Auth::guard('web')->setUser($this->user);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('patient_medication_doses');
        Schema::dropIfExists('patient_medication_plans');
        Schema::dropIfExists('patient_account_accesses');

        parent::tearDown();
    }

    private function createPlan(string $status = 'active'): PatientMedicationPlan
    {
        $scheduled = Carbon::now('Asia/Tehran')->subMinutes(5);
        $this->scheduledFor = $scheduled->format('Y-m-d H:i:s');

        DB::table('patient_account_accesses')->insert([
            'id' => 71,
            'patient_id' => 41,
            'user_id' => $this->user->id,
            'access_type' => 'owner',
            'permissions' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('patient_medication_plans')->insert([
            'id' => 501,
            'patient_id' => 41,
            'user_id' => $this->user->id,
            'prescription_id' => 601,
            'start_date' => $scheduled->toDateString(),
            'end_date' => null,
            'reminder_times' => json_encode([$scheduled->format('H:i')]),
            'timezone' => 'Asia/Tehran',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return PatientMedicationPlan::query()->findOrFail(501);
    }

    private function doseRequest(): Request
    {
        return Request::create('/my-health/medications/501/doses', 'POST', [
            'scheduled_for' => $this->scheduledFor,
            'status' => 'taken',
            'note' => 'Patient-entered note',
        ]);
    }

    public function test_dose_is_rejected_when_a_stale_route_model_points_to_a_paused_plan(): void
    {
        $plan = $this->createPlan();

        // Simulate another request pausing the plan after route model binding.
        DB::table('patient_medication_plans')
            ->where('id', $plan->id)
            ->update(['status' => 'paused']);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        try {
            (new PatientMedicationController())->recordDose($this->doseRequest(), $plan, $audit);
            $this->fail('A dose must not be recorded after the plan has been paused.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }

        $this->assertDatabaseMissing('patient_medication_doses', [
            'plan_id' => $plan->id,
        ]);
    }

    public function test_active_owned_plan_records_a_dose_and_audits_the_action(): void
    {
        $plan = $this->createPlan();

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('patient.medication.dose_recorded', $plan);

        $response = (new PatientMedicationController())->recordDose(
            $this->doseRequest(),
            $plan,
            $audit
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertDatabaseHas('patient_medication_doses', [
            'plan_id' => $plan->id,
            'status' => 'taken',
            'recorded_by_user_id' => $this->user->id,
        ]);
    }

    public function test_pausing_a_plan_rechecks_the_owner_grant_and_persists_the_state(): void
    {
        $plan = $this->createPlan();

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('patient.medication.reminder_paused', $plan);

        $response = (new PatientMedicationController())->pause(
            Request::create('/my-health/medications/501/pause', 'POST'),
            $plan,
            $audit
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('paused', $plan->fresh()->status);
    }
}
