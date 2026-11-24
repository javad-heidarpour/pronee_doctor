<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Patient\ListPatient360FollowUpsAction;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientFollowUpTask;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ListPatient360FollowUpsActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('patient_follow_up_tasks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->string('priority')->default('normal');
            $table->string('status');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('patient_follow_up_tasks');

        parent::tearDown();
    }

    public function test_secretary_only_receives_follow_ups_from_assigned_clinics(): void
    {
        $doctor = new Doctor();
        $doctor->id = 11;

        $patient = new Patient();
        $patient->id = 41;

        $visible = $this->createTask(31, 'Visible clinic task');
        $otherClinic = $this->createTask(32, 'Other clinic task');
        $unassignedLegacy = $this->createTask(null, 'Unassigned legacy task');

        $secretary = new User();
        $secretary->role = 'secretary';

        $tasks = app(ListPatient360FollowUpsAction::class)->handle(
            $secretary,
            $doctor,
            $patient,
            [31, 31]
        );

        $this->assertSame([$visible->id], $tasks->pluck('id')->all());
        $this->assertNotContains($otherClinic->id, $tasks->pluck('id')->all());
        $this->assertNotContains($unassignedLegacy->id, $tasks->pluck('id')->all());
    }

    public function test_doctor_retains_legacy_unassigned_follow_ups_without_cross_clinic_access(): void
    {
        $doctor = new Doctor();
        $doctor->id = 11;

        $patient = new Patient();
        $patient->id = 41;

        $visible = $this->createTask(31, 'Visible clinic task');
        $otherClinic = $this->createTask(32, 'Other clinic task');
        $legacy = $this->createTask(null, 'Unassigned legacy task');

        $user = new User();
        $user->role = 'doctor';

        $tasks = app(ListPatient360FollowUpsAction::class)->handle(
            $user,
            $doctor,
            $patient,
            [31]
        );

        $this->assertEqualsCanonicalizing(
            [$visible->id, $legacy->id],
            $tasks->pluck('id')->all()
        );
        $this->assertNotContains($otherClinic->id, $tasks->pluck('id')->all());
    }

    private function createTask(?int $clinicId, string $title): PatientFollowUpTask
    {
        return PatientFollowUpTask::query()->create([
            'doctor_id' => 11,
            'patient_id' => 41,
            'clinic_id' => $clinicId,
            'title' => $title,
            'description' => 'Private follow-up details',
            'due_at' => now()->addDay(),
            'priority' => 'normal',
            'status' => 'pending',
        ]);
    }
}
