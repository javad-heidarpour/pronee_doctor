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
