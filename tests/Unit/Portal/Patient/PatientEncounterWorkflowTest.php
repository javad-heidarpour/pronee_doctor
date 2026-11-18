<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Patient\CreatePatientEncounterAction;
use App\Actions\Portal\Patient\SignPatientEncounterAction;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientEncounter;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class PatientEncounterWorkflowTest extends TestCase
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

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('clinic_id');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('patient_encounters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('amends_encounter_id')->nullable();
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('author_id')->nullable();
            $table->unsignedBigInteger('signed_by')->nullable();
            $table->string('note_type');
            $table->string('status');
            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('assessment');
            $table->text('plan');
            $table->string('diagnosis')->nullable();
            $table->string('diagnosis_code')->nullable();
            $table->boolean('reviewed_allergies')->default(false);
            $table->boolean('reviewed_medications')->default(false);
            $table->boolean('reviewed_vitals')->default(false);
            $table->json('clinical_snapshot')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_encounter_prescriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('encounter_id');
            $table->string('medication_name');
            $table->string('strength')->nullable();
            $table->string('dosage');
            $table->string('route');
            $table->string('frequency');
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->string('quantity')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function test_it_creates_a_structured_draft_with_prescriptions_and_a_clinical_snapshot(): void
    {
        $doctor = new Doctor();
        $doctor->id = 11;
        $author = new User();
        $author->id = 21;

        $patient = Patient::query()->create([
            'doctor_id' => 11,
            'name' => 'Test Patient',
        ]);

        $appointment = Appointment::query()->create([
            'doctor_id' => 11,
            'patient_id' => $patient->id,
            'clinic_id' => 31,
            'status' => 'confirmed',
        ]);

        $encounter = app(CreatePatientEncounterAction::class)->handle(
            $doctor,
            $author,
            $appointment,
            [
                'note_type' => 'consultation',
                'subjective' => 'Headache for two days',
                'objective' => 'No focal neurological deficit',
                'assessment' => 'Clinical assessment',
                'plan' => 'Follow up in one week',
                'diagnosis' => 'Headache',
                'diagnosis_code' => 'R51',
                'reviewed_allergies' => true,
                'reviewed_medications' => true,
                'reviewed_vitals' => true,
                'clinical_snapshot' => ['allergies' => [], 'medications' => [], 'vitals' => []],
                'prescriptions' => [[
                    'medication_name' => 'Example medicine',
                    'strength' => '500 mg',
                    'dosage' => 'One tablet',
                    'route' => 'oral',
                    'frequency' => 'Twice daily',
                    'duration_days' => 5,
                    'quantity' => '10 tablets',
                    'instructions' => 'After meals',
                ]],
            ]
        );

        $this->assertSame(PatientEncounter::STATUS_DRAFT, $encounter->status);
        $this->assertSame(11, (int) $encounter->doctor_id);
        $this->assertSame(21, (int) $encounter->author_id);
        $this->assertSame('R51', $encounter->diagnosis_code);
        $this->assertSame(1, $encounter->prescriptions->count());
        $this->assertSame('Example medicine', $encounter->prescriptions->first()->medication_name);
        $this->assertSame([], $encounter->clinical_snapshot['allergies']);
    }

    public function test_signing_requires_all_safety_reviews_and_marks_the_appointment_visited(): void
    {
        $doctor = new Doctor();
        $doctor->id = 12;
        $signer = new User();
        $signer->id = 22;

        $patient = Patient::query()->create(['doctor_id' => 12, 'name' => 'Patient']);
        $appointment = Appointment::query()->create([
            'doctor_id' => 12, 'patient_id' => $patient->id, 'clinic_id' => 32, 'status' => 'confirmed',
        ]);

        $encounter = PatientEncounter::query()->create([
            'doctor_id' => 12, 'patient_id' => $patient->id, 'appointment_id' => $appointment->id,
            'clinic_id' => 32, 'author_id' => 22, 'note_type' => 'consultation', 'status' => 'draft',
            'assessment' => 'Assessment', 'plan' => 'Plan',
            'reviewed_allergies' => false, 'reviewed_medications' => true, 'reviewed_vitals' => true,
        ]);

        try {
            app(SignPatientEncounterAction::class)->handle($encounter, $signer);
            $this->fail('Signing without allergy review must be rejected.');
        } catch (ValidationException) {
            $this->assertSame('draft', $encounter->fresh()->status);
        }

        $encounter->update(['reviewed_allergies' => true]);
        $signed = app(SignPatientEncounterAction::class)->handle($encounter->fresh(), $signer);

        $this->assertSame(PatientEncounter::STATUS_SIGNED, $signed->status);
        $this->assertSame(22, (int) $signed->signed_by);
        $this->assertNotNull($signed->signed_at);
        $this->assertSame('visited', DB::table('appointments')->where('id', $appointment->id)->value('status'));
    }

    public function test_a_correction_creates_a_new_encounter_without_mutating_the_signed_original(): void
    {
        $doctor = new Doctor();
        $doctor->id = 13;
        $author = new User();
        $author->id = 23;
        $patient = Patient::query()->create(['doctor_id' => 13, 'name' => 'Patient']);
        $appointment = Appointment::query()->create([
            'doctor_id' => 13, 'patient_id' => $patient->id, 'clinic_id' => 33, 'status' => 'visited',
        ]);

        $original = PatientEncounter::query()->create([
            'doctor_id' => 13, 'patient_id' => $patient->id, 'appointment_id' => $appointment->id,
            'clinic_id' => 33, 'author_id' => 23, 'signed_by' => 23, 'note_type' => 'consultation',
            'status' => 'signed', 'assessment' => 'Original assessment', 'plan' => 'Original plan',
            'reviewed_allergies' => true, 'reviewed_medications' => true, 'reviewed_vitals' => true,
            'signed_at' => now(),
        ]);

        $amendment = app(CreatePatientEncounterAction::class)->handle($doctor, $author, $appointment, [
            'amends_encounter_id' => $original->id,
            'note_type' => 'consultation',
            'subjective' => 'Correction context',
            'objective' => 'Objective',
            'assessment' => 'Corrected assessment',
            'plan' => 'Corrected plan',
            'reviewed_allergies' => true,
            'reviewed_medications' => true,
            'reviewed_vitals' => true,
            'clinical_snapshot' => [],
            'prescriptions' => [],
        ]);

        $this->assertSame($original->id, (int) $amendment->amends_encounter_id);
        $this->assertSame('draft', $amendment->status);
        $this->assertSame('Original assessment', $original->fresh()->assessment);
        $this->assertSame('signed', $original->fresh()->status);
    }

}