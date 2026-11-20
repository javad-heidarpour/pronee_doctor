<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\PatientFollowUpController;
use App\Models\Doctor;
use App\Models\PatientFollowUpTask;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

final class PatientFollowUpUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('patient_follow_up_tasks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('status');
            $table->string('outcome')->nullable();
            $table->text('result_notes')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->dateTime('due_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('patient_follow_up_tasks');
        parent::tearDown();
    }

    public function test_it_completes_only_a_pending_follow_up_and_records_completion_time(): void
    {
        $taskId = $this->createTask('pending');
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with(
                'patient.follow_up.updated',
                Mockery::type(PatientFollowUpTask::class),
                [
                    'doctor_id' => 44,
                    'status_changed' => true,
                    'changed_fields' => ['status'],
                ]
            );

        $response = $this->controller()->update(
            Request::create('/portal/follow-ups/' . $taskId, 'PUT', ['status' => 'completed']),
            $taskId,
            $audit
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $task = DB::table('patient_follow_up_tasks')->find($taskId);
        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_it_rejects_changes_to_a_terminal_follow_up(): void
    {
        $taskId = $this->createTask('completed');
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        $this->expectException(ModelNotFoundException::class);

        $this->controller()->update(
            Request::create('/portal/follow-ups/' . $taskId, 'PUT', ['status' => 'pending']),
            $taskId,
            $audit
        );
    }

    public function test_it_rejects_rescheduling_to_a_past_date(): void
    {
        $taskId = $this->createTask('pending');
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        $this->expectException(ValidationException::class);

        $this->controller()->update(
            Request::create('/portal/follow-ups/' . $taskId, 'PUT', [
                'status' => 'pending',
                'due_at' => now()->subDay()->toDateTimeString(),
            ]),
            $taskId,
            $audit
        );
    }

    private function controller(): PatientFollowUpController
    {
        $doctor = new Doctor();
        $doctor->id = 44;

        return new class($doctor) extends PatientFollowUpController {
            public function __construct(private Doctor $testDoctor)
            {
            }

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
    }

    private function createTask(string $status): int
    {
        return DB::table('patient_follow_up_tasks')->insertGetId([
            'doctor_id' => 44,
            'clinic_id' => 31,
            'patient_id' => 12,
            'status' => $status,
            'due_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
