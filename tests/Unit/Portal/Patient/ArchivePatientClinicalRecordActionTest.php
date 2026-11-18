<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Patient\ArchivePatientClinicalRecordAction;
use App\Actions\Portal\Patient\RestorePatientClinicalRecordAction;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientClinicalRecord;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ArchivePatientClinicalRecordActionTest extends TestCase
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
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_it_soft_deletes_a_clinical_record_for_its_patient(): void
    {
        $doctor = Doctor::query()->create(['name' => 'Doctor', 'slug' => 'doctor']);
        $patient = Patient::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Patient',
        ]);
        $record = PatientClinicalRecord::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'kind' => PatientClinicalRecord::KIND_ALLERGY,
            'title' => 'Penicillin',
        ]);

        app(ArchivePatientClinicalRecordAction::class)->handle($patient, $record);

        $this->assertSoftDeleted('patient_clinical_records', ['id' => $record->id]);
        $this->assertDatabaseHas('patient_clinical_records', [
            'id' => $record->id,
            'title' => 'Penicillin',
        ]);
    }

    public function test_it_rejects_archiving_a_record_from_another_patient(): void
    {
        $doctor = Doctor::query()->create(['name' => 'Doctor', 'slug' => 'doctor']);
        $patient = Patient::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Patient A',
        ]);
        $otherPatient = Patient::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Patient B',
        ]);
        $record = PatientClinicalRecord::query()->create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $doctor->id,
            'kind' => PatientClinicalRecord::KIND_HISTORY,
            'title' => 'Private record',
        ]);

        $this->expectException(ModelNotFoundException::class);

        app(ArchivePatientClinicalRecordAction::class)->handle($patient, $record);
    }

    public function test_it_rejects_archiving_a_record_owned_by_another_doctor(): void
    {
        $doctorA = Doctor::query()->create(['name' => 'Doctor A', 'slug' => 'doctor-a']);
        $doctorB = Doctor::query()->create(['name' => 'Doctor B', 'slug' => 'doctor-b']);
        $patient = Patient::query()->create([
            'doctor_id' => $doctorA->id,
            'name' => 'Patient',
        ]);
        $record = PatientClinicalRecord::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorB->id,
            'kind' => PatientClinicalRecord::KIND_MEDICATION,
            'title' => 'Private medication',
        ]);

        $this->expectException(ModelNotFoundException::class);

        app(ArchivePatientClinicalRecordAction::class)->handle($patient, $record);
    }
    public function test_it_restores_an_archived_record_for_its_patient(): void
    {
        $doctor = Doctor::query()->create(['name' => 'Doctor', 'slug' => 'doctor-restore']);
        $patient = Patient::query()->create(['doctor_id' => $doctor->id, 'name' => 'Patient']);
        $record = PatientClinicalRecord::query()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'kind' => PatientClinicalRecord::KIND_ALLERGY,
            'title' => 'Archived allergy',
        ]);
        $record->delete();

        $restored = app(RestorePatientClinicalRecordAction::class)->handle($patient, $record);

        $this->assertNull($restored->deleted_at);
        $this->assertDatabaseHas('patient_clinical_records', [
            'id' => $record->id,
            'deleted_at' => null,
        ]);
    }

    public function test_it_rejects_restoring_a_record_from_another_patient(): void
    {
        $doctor = Doctor::query()->create(['name' => 'Doctor', 'slug' => 'doctor-restore-scope']);
        $patient = Patient::query()->create(['doctor_id' => $doctor->id, 'name' => 'Patient A']);
        $otherPatient = Patient::query()->create(['doctor_id' => $doctor->id, 'name' => 'Patient B']);
        $record = PatientClinicalRecord::query()->create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $doctor->id,
            'kind' => PatientClinicalRecord::KIND_HISTORY,
            'title' => 'Foreign archived record',
        ]);
        $record->delete();

        $this->expectException(ModelNotFoundException::class);

        app(RestorePatientClinicalRecordAction::class)->handle($patient, $record);
    }

}
