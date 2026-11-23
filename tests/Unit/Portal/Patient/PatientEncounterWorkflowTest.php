<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Patient\CreatePatientEncounterAction;
use App\Actions\Portal\Patient\SignPatientEncounterAction;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\PatientEncounterController;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientEncounter;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

final class PatientEncounterWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('family')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

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
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('time_slot_id')->nullable();
            $table->string('patient_name')->nullable();
            $table->string('patient_phone')->nullable();
            $table->string('patient_national_code')->nullable();
            $table->string('visit_number')->nullable();
            $table->unsignedBigInteger('fee_rial')->nullable();
            $table->dateTime('reservation_expires_at')->nullable();
            $table->unsignedBigInteger('visit_reason_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('surgery_type_id')->nullable();
            $table->date('surgery_date')->nullable();
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

        Schema::create('patient_clinical_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->string('kind');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_vital_signs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->string('measurement_type');
            $table->decimal('value', 10, 2);
            $table->string('unit');
            $table->dateTime('measured_at');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function test_reading_encounter_forms_is_audited_without_clinical_values(): void
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
            'name' => 'Audit Patient',
            'phone' => '09120000000',
            'national_code' => '0012345678',
        ]);
        $appointment = Appointment::query()->create([
            'doctor_id' => 11,
            'patient_id' => $patient->id,
            'clinic_id' => 31,
            'user_id' => 1234,
            'patient_name' => 'Audit Patient',
            'patient_phone' => '09120000000',
            'patient_national_code' => '0012345678',
            'fee_rial' => 1250000,
            'reservation_expires_at' => now()->addHour(),
            'description' => 'Sensitive booking details',
            'status' => 'confirmed',
        ]);
        $encounter = PatientEncounter::query()->create([
            'doctor_id' => 11,
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'clinic_id' => 31,
            'note_type' => 'consultation',
            'status' => PatientEncounter::STATUS_SIGNED,
            'assessment' => 'Sensitive assessment',
            'plan' => 'Sensitive plan',
            'reviewed_allergies' => true,
            'reviewed_medications' => true,
            'reviewed_vitals' => true,
        ]);

        $user = new User();
        $user->id = 99;
        $user->role = 'doctor';
        Auth::guard('web')->setUser($user);
        session(['active_doctor_id' => 11]);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('patient.encounter.viewed', Mockery::type(PatientEncounter::class), ['doctor_id' => 11]);
        $audit->shouldReceive('handle')
            ->once()
            ->with('patient.encounter.amend_form_viewed', Mockery::type(PatientEncounter::class), ['doctor_id' => 11]);
        $audit->shouldReceive('handle')
            ->once()
            ->with('patient.encounter.create_form_viewed', Mockery::type(Appointment::class), ['doctor_id' => 11]);

        $controller = new PatientEncounterController();
        $request = Request::create('/portal/encounters', 'GET');
        $request->headers->set('X-Inertia', 'true');

        $show = $controller->show($encounter, $audit);
        $showProps = $show->toResponse($request)->getData(true)['props'];
        $this->assertSame([
            'id' => $appointment->id,
            'clinic' => ['id' => 31, 'name' => 'Test Clinic'],
        ], $showProps['appointment']);
        $this->assertSame(['id' => $patient->id, 'name' => 'Audit Patient'], $showProps['patient']);
        foreach (['doctor_id', 'patient_id', 'appointment_id', 'clinic_id', 'author_id', 'signed_by'] as $field) {
            $this->assertArrayNotHasKey($field, $showProps['encounter']);
        }
        foreach (['patient', 'appointment', 'clinic', 'author'] as $relation) {
            $this->assertArrayNotHasKey($relation, $showProps['encounter']);
        }
        $this->assertArrayNotHasKey('patient_phone', $showProps['appointment']);
        $this->assertArrayNotHasKey('patient_national_code', $showProps['appointment']);
        $this->assertArrayNotHasKey('user_id', $showProps['appointment']);
        $this->assertArrayNotHasKey('fee_rial', $showProps['appointment']);
        $this->assertArrayNotHasKey('description', $showProps['appointment']);

        $amend = $controller->amend($encounter, $audit);
        $amendProps = $amend->toResponse($request)->getData(true)['props'];
        $this->assertSame(['id' => $patient->id, 'name' => 'Audit Patient'], $amendProps['patient']);
        $this->assertArrayNotHasKey('patient_id', $amendProps['amendsEncounter']);
        $this->assertArrayNotHasKey('appointment_id', $amendProps['amendsEncounter']);

        $create = $controller->create($appointment, $audit);
        $createProps = $create->toResponse($request)->getData(true)['props'];
        $this->assertSame(['id' => $patient->id, 'name' => 'Audit Patient'], $createProps['patient']);
        $this->assertSame([
            'id' => $appointment->id,
            'clinic' => ['id' => 31, 'name' => 'Test Clinic'],
        ], $createProps['appointment']);
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
        DB::table('users')->insert([
            'id' => 22, 'name' => 'Test', 'family' => 'Doctor',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('addresses')->insert([
            'id' => 32, 'name' => 'Test Clinic', 'created_at' => now(), 'updated_at' => now(),
        ]);
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

        try {
            $signed->update(['assessment' => 'Tampered']);
            $this->fail('Signed clinical notes must be immutable.');
        } catch (ValidationException) {
            $this->assertSame('Assessment', $signed->fresh()->assessment);
        }
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