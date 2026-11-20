<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Patient\SavePatientProblemAction;
use App\Models\Patient;
use App\Models\PatientEncounter;
use App\Models\PatientProblem;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class SavePatientProblemActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('national_code')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_encounters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->string('note_type')->nullable();
            $table->string('status');
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_problems', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('encounter_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('category', 32);
            $table->string('title', 180);
            $table->string('code', 64)->nullable();
            $table->string('code_system', 32);
            $table->text('description')->nullable();
            $table->string('status', 20);
            $table->string('severity', 20)->nullable();
            $table->date('onset_date')->nullable();
            $table->date('resolved_on')->nullable();
            $table->string('source', 32);
            $table->timestamps();
        });
    }

    public function test_it_creates_a_structured_diagnosis_for_the_patient_and_doctor(): void
    {
        $patient = $this->makePatient(11);
        $actor = $this->makeActor(21);

        $problem = app(SavePatientProblemAction::class)->handle($patient, $actor, [
            'category' => 'diagnosis',
            'title' => 'Type 2 diabetes mellitus',
            'code' => 'E11.9',
            'code_system' => 'icd10',
            'description' => 'Diagnosis confirmed by clinician.',
            'status' => 'active',
            'severity' => 'moderate',
            'onset_date' => '2024-03-01',
            'source' => 'clinician_assessed',
        ]);

        $this->assertSame($patient->id, $problem->patient_id);
        $this->assertSame(11, $problem->doctor_id);
        $this->assertSame('E11.9', $problem->code);
        $this->assertSame('active', $problem->status);
        $this->assertSame(21, $problem->created_by);
        $this->assertDatabaseHas('patient_problems', [
            'id' => $problem->id,
            'patient_id' => $patient->id,
            'doctor_id' => 11,
            'code' => 'E11.9',
        ]);
    }

    public function test_resolving_a_problem_sets_resolution_date_and_reopening_clears_it(): void
    {
        $patient = $this->makePatient(12);
        $actor = $this->makeActor(22);
        $action = app(SavePatientProblemAction::class);
        $encounter = PatientEncounter::query()->create([
            'doctor_id' => 12,
            'patient_id' => $patient->id,
            'clinic_id' => 120,
            'status' => PatientEncounter::STATUS_SIGNED,
        ]);

        $problem = $action->handle($patient, $actor, [
            'category' => 'symptom',
            'title' => 'Headache',
            'code_system' => 'local',
            'status' => 'active',
            'source' => 'patient_reported',
            'encounter_id' => $encounter->id,
        ]);

        $resolved = $action->handle($patient, $actor, [
            'category' => 'symptom',
            'title' => 'Headache',
            'code_system' => 'local',
            'status' => 'resolved',
            'source' => 'patient_reported',
        ], $problem);

        $this->assertSame('resolved', $resolved->status);
        $this->assertSame(now()->toDateString(), $resolved->resolved_on->format('Y-m-d'));
        $this->assertSame($encounter->id, $resolved->encounter_id);

        $reopened = $action->handle($patient, $actor, [
            'category' => 'symptom',
            'title' => 'Recurrent headache',
            'code_system' => 'local',
            'status' => 'active',
            'source' => 'patient_reported',
        ], $resolved);

        $this->assertSame('active', $reopened->status);
        $this->assertNull($reopened->resolved_on);
        $this->assertSame($encounter->id, $reopened->encounter_id);
        $this->assertSame(22, $reopened->updated_by);
    }

    public function test_it_only_links_a_signed_encounter_of_the_same_patient_and_doctor(): void
    {
        $patient = $this->makePatient(13);
        $otherPatient = $this->makePatient(13);
        $actor = $this->makeActor(23);
        $encounter = PatientEncounter::query()->create([
            'doctor_id' => 13,
            'patient_id' => $otherPatient->id,
            'status' => PatientEncounter::STATUS_SIGNED,
        ]);

        try {
            app(SavePatientProblemAction::class)->handle($patient, $actor, [
                'category' => 'diagnosis',
                'title' => 'Foreign diagnosis',
                'code_system' => 'local',
                'status' => 'active',
                'source' => 'clinician_assessed',
                'encounter_id' => $encounter->id,
            ]);
            $this->fail('A problem must not reference another patient encounter.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('encounter_id', $exception->errors());
            $this->assertDatabaseCount('patient_problems', 0);
        }
    }

    public function test_it_rejects_linking_to_an_encounter_outside_accessible_clinics(): void
    {
        $patient = $this->makePatient(16);
        $actor = $this->makeActor(26);
        $encounter = PatientEncounter::query()->create([
            'doctor_id' => 16,
            'patient_id' => $patient->id,
            'clinic_id' => 99,
            'status' => PatientEncounter::STATUS_SIGNED,
        ]);

        $this->expectException(ValidationException::class);
        app(SavePatientProblemAction::class)->handle($patient, $actor, [
            'category' => 'diagnosis',
            'title' => 'Restricted clinic diagnosis',
            'code_system' => 'local',
            'status' => 'active',
            'source' => 'clinician_assessed',
            'encounter_id' => $encounter->id,
        ], null, [77]);
    }

    public function test_it_rejects_linking_to_a_draft_encounter(): void
    {
        $patient = $this->makePatient(14);
        $actor = $this->makeActor(24);
        $encounter = PatientEncounter::query()->create([
            'doctor_id' => 14,
            'patient_id' => $patient->id,
            'status' => PatientEncounter::STATUS_DRAFT,
        ]);

        $this->expectException(ValidationException::class);
        app(SavePatientProblemAction::class)->handle($patient, $actor, [
            'category' => 'diagnosis',
            'title' => 'Draft linked diagnosis',
            'code_system' => 'local',
            'status' => 'active',
            'source' => 'clinician_assessed',
            'encounter_id' => $encounter->id,
        ]);
    }

    public function test_it_rejects_updating_a_problem_owned_by_another_patient(): void
    {
        $patient = $this->makePatient(15);
        $otherPatient = $this->makePatient(15);
        $actor = $this->makeActor(25);
        $problem = PatientProblem::query()->create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => 15,
            'category' => 'condition',
            'title' => 'Private problem',
            'code_system' => 'local',
            'status' => 'active',
            'source' => 'clinician_assessed',
        ]);

        $this->expectException(ModelNotFoundException::class);
        app(SavePatientProblemAction::class)->handle($patient, $actor, [
            'category' => 'condition',
            'title' => 'Attempted change',
            'code_system' => 'local',
            'status' => 'active',
            'source' => 'clinician_assessed',
        ], $problem);
    }

    private function makePatient(int $doctorId): Patient
    {
        return Patient::query()->create([
            'doctor_id' => $doctorId,
            'name' => 'Test Patient',
        ]);
    }

    private function makeActor(int $id): User
    {
        $actor = new User();
        $actor->id = $id;

        return $actor;
    }
}
