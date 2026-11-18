<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Patient\SavePatientClinicalRecordAction;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientClinicalRecord;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SavePatientClinicalRecordActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->string('phone', 45)->nullable();
            $table->string('national_code', 20)->nullable();
            $table->timestamps();
        });
        Schema::create('patient_clinical_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->string('kind', 32);
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('dosage', 120)->nullable();
            $table->string('frequency', 120)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('related_record_id')->nullable();
            $table->unsignedBigInteger('related_allergy_record_id')->nullable();
            $table->string('reaction_severity', 24)->nullable();
            $table->date('reaction_date')->nullable();
            $table->text('action_taken')->nullable();
            $table->string('reaction_outcome', 24)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_it_creates_a_record_for_the_given_patient(): void
    {
        $doctor = $this->makeDoctor('doctor-a');
        $patient = $this->makePatient($doctor);
        $record = app(SavePatientClinicalRecordAction::class)->handle($patient, [
            'kind' => 'allergy',
            'title' => 'Test allergy',
            'description' => 'Test details',
            'is_active' => true,
        ]);

        $this->assertSame($patient->id, $record->patient_id);
        $this->assertSame($doctor->id, $record->doctor_id);
        $this->assertDatabaseHas('patient_clinical_records', [
            'id' => $record->id,
            'patient_id' => $patient->id,
            'kind' => 'allergy',
        ]);
    }

    public function test_it_updates_a_record_belonging_to_the_same_patient(): void
    {
        $doctor = $this->makeDoctor('doctor-b');
        $patient = $this->makePatient($doctor);
        $record = PatientClinicalRecord::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'kind' => 'medication',
            'title' => 'Old title',
        ]);

        $updated = app(SavePatientClinicalRecordAction::class)->handle($patient, [
            'kind' => 'medication',
            'title' => 'New title',
            'dosage' => '500 mg',
            'is_active' => true,
        ], $record);

        $this->assertSame('New title', $updated->title);
    }

    public function test_it_links_a_reported_adverse_reaction_to_a_medication_for_the_same_patient(): void
    {
        $doctor = $this->makeDoctor('doctor-reaction');
        $patient = $this->makePatient($doctor);
        $medication = PatientClinicalRecord::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'kind' => PatientClinicalRecord::KIND_MEDICATION,
            'title' => 'Medication A',
        ]);

        $reaction = app(SavePatientClinicalRecordAction::class)->handle($patient, [
            'kind' => PatientClinicalRecord::KIND_ADVERSE_REACTION,
            'title' => 'Reported rash',
            'description' => 'Patient reported a rash after taking the medication.',
            'related_record_id' => $medication->id,
        ]);

        $this->assertSame($medication->id, $reaction->related_record_id);
        $this->assertSame('Medication A', $reaction->relatedRecord->title);
    }

    public function test_it_rejects_linking_an_adverse_reaction_to_another_patients_medication(): void
    {
        $doctor = $this->makeDoctor('doctor-reaction-scope');
        $patient = $this->makePatient($doctor);
        $otherPatient = $this->makePatient($doctor);
        $medication = PatientClinicalRecord::query()->create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $doctor->id,
            'kind' => PatientClinicalRecord::KIND_MEDICATION,
            'title' => 'Other patient medication',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(SavePatientClinicalRecordAction::class)->handle($patient, [
            'kind' => PatientClinicalRecord::KIND_ADVERSE_REACTION,
            'title' => 'Reported reaction',
            'related_record_id' => $medication->id,
        ]);
    }

    public function test_it_links_a_reaction_to_an_allergy_for_the_same_patient(): void
    {
        $doctor = $this->makeDoctor('doctor-allergy-link');
        $patient = $this->makePatient($doctor);
        $allergy = PatientClinicalRecord::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'kind' => PatientClinicalRecord::KIND_ALLERGY,
            'title' => 'Penicillin allergy',
        ]);

        $reaction = app(SavePatientClinicalRecordAction::class)->handle($patient, [
            'kind' => PatientClinicalRecord::KIND_ADVERSE_REACTION,
            'title' => 'Reported rash',
            'related_allergy_record_id' => $allergy->id,
        ]);

        $this->assertSame($allergy->id, $reaction->related_allergy_record_id);
        $this->assertSame('Penicillin allergy', $reaction->relatedAllergyRecord->title);
    }

    public function test_it_rejects_an_allergy_link_from_another_patient(): void
    {
        $doctor = $this->makeDoctor('doctor-allergy-scope');
        $patient = $this->makePatient($doctor);
        $otherPatient = $this->makePatient($doctor);
        $allergy = PatientClinicalRecord::query()->create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $doctor->id,
            'kind' => PatientClinicalRecord::KIND_ALLERGY,
            'title' => 'Other patient allergy',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(SavePatientClinicalRecordAction::class)->handle($patient, [
            'kind' => PatientClinicalRecord::KIND_ADVERSE_REACTION,
            'title' => 'Reported reaction',
            'related_allergy_record_id' => $allergy->id,
        ]);
    }

    public function test_it_saves_structured_details_for_a_reported_adverse_reaction(): void
    {
        $doctor = $this->makeDoctor('doctor-reaction-details');
        $patient = $this->makePatient($doctor);

        $reaction = app(SavePatientClinicalRecordAction::class)->handle($patient, [
            'kind' => PatientClinicalRecord::KIND_ADVERSE_REACTION,
            'title' => 'Reported dizziness',
            'reaction_severity' => 'moderate',
            'reaction_date' => '2026-11-18',
            'action_taken' => 'Patient reported contacting the clinic.',
            'reaction_outcome' => 'ongoing',
        ]);

        $this->assertSame('moderate', $reaction->reaction_severity);
        $this->assertSame('2026-11-18', $reaction->reaction_date->format('Y-m-d'));
        $this->assertSame('Patient reported contacting the clinic.', $reaction->action_taken);
        $this->assertSame('ongoing', $reaction->reaction_outcome);
    }

    public function test_it_clears_reaction_details_when_record_kind_changes(): void
    {
        $doctor = $this->makeDoctor('doctor-reaction-clear');
        $patient = $this->makePatient($doctor);
        $record = PatientClinicalRecord::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'kind' => PatientClinicalRecord::KIND_ADVERSE_REACTION,
            'title' => 'Reported reaction',
            'reaction_severity' => 'severe',
            'reaction_date' => '2026-11-17',
            'action_taken' => 'Observed',
            'reaction_outcome' => 'unknown',
        ]);

        $updated = app(SavePatientClinicalRecordAction::class)->handle($patient, [
            'kind' => PatientClinicalRecord::KIND_ALLERGY,
            'title' => 'Updated allergy',
        ], $record);

        $this->assertNull($updated->reaction_severity);
        $this->assertNull($updated->reaction_date);
        $this->assertNull($updated->action_taken);
        $this->assertNull($updated->reaction_outcome);
    }

    public function test_it_rejects_a_record_belonging_to_another_patient(): void
    {
        $doctor = $this->makeDoctor('doctor-c');
        $patient = $this->makePatient($doctor);
        $otherPatient = $this->makePatient($doctor);
        $record = PatientClinicalRecord::query()->create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $doctor->id,
            'kind' => 'medical_history',
            'title' => 'Scoped record',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(SavePatientClinicalRecordAction::class)->handle($patient, [
            'kind' => 'medical_history',
            'title' => 'Unauthorized update',
        ], $record);
    }

    public function test_it_rejects_linking_a_reaction_to_an_inactive_medication(): void
    {
        $doctor = $this->makeDoctor('doctor-inactive-medication');
        $patient = $this->makePatient($doctor);
        $medication = PatientClinicalRecord::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'kind' => PatientClinicalRecord::KIND_MEDICATION,
            'title' => 'Inactive medication',
            'is_active' => false,
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(SavePatientClinicalRecordAction::class)->handle($patient, [
            'kind' => PatientClinicalRecord::KIND_ADVERSE_REACTION,
            'title' => 'Reported reaction',
            'related_record_id' => $medication->id,
        ]);
    }

    public function test_it_rejects_linking_a_reaction_to_an_inactive_allergy(): void
    {
        $doctor = $this->makeDoctor('doctor-inactive-allergy');
        $patient = $this->makePatient($doctor);
        $allergy = PatientClinicalRecord::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'kind' => PatientClinicalRecord::KIND_ALLERGY,
            'title' => 'Inactive allergy',
            'is_active' => false,
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(SavePatientClinicalRecordAction::class)->handle($patient, [
            'kind' => PatientClinicalRecord::KIND_ADVERSE_REACTION,
            'title' => 'Reported reaction',
            'related_allergy_record_id' => $allergy->id,
        ]);
    }

    public function test_it_rejects_a_medication_link_with_a_mismatched_doctor_id(): void
    {
        $doctor = $this->makeDoctor('doctor-link-owner');
        $otherDoctor = $this->makeDoctor('doctor-link-other');
        $patient = $this->makePatient($doctor);
        $medication = PatientClinicalRecord::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $otherDoctor->id,
            'kind' => PatientClinicalRecord::KIND_MEDICATION,
            'title' => 'Mismatched medication',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(SavePatientClinicalRecordAction::class)->handle($patient, [
            'kind' => PatientClinicalRecord::KIND_ADVERSE_REACTION,
            'title' => 'Reported reaction',
            'related_record_id' => $medication->id,
        ]);
    }

    public function test_it_clears_associations_when_reaction_is_changed_to_another_kind(): void
    {
        $doctor = $this->makeDoctor('doctor-clear-associations');
        $patient = $this->makePatient($doctor);
        $medication = PatientClinicalRecord::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'kind' => PatientClinicalRecord::KIND_MEDICATION,
            'title' => 'Medication',
        ]);
        $allergy = PatientClinicalRecord::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'kind' => PatientClinicalRecord::KIND_ALLERGY,
            'title' => 'Allergy',
        ]);
        $reaction = PatientClinicalRecord::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'kind' => PatientClinicalRecord::KIND_ADVERSE_REACTION,
            'title' => 'Reported reaction',
            'related_record_id' => $medication->id,
            'related_allergy_record_id' => $allergy->id,
        ]);

        $updated = app(SavePatientClinicalRecordAction::class)->handle($patient, [
            'kind' => PatientClinicalRecord::KIND_HISTORY,
            'title' => 'Medical history',
        ], $reaction);

        $this->assertNull($updated->related_record_id);
        $this->assertNull($updated->related_allergy_record_id);
    }

    private function makeDoctor(string $slug): Doctor
    {
        return Doctor::query()->create(['name' => 'Test Doctor', 'slug' => $slug]);
    }

    private function makePatient(Doctor $doctor): Patient
    {
        return Patient::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Test Patient',
            'phone' => '09120000000',
            'national_code' => '0012345678',
        ]);
    }
}
