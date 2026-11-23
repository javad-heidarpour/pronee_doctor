<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Patient\CreatePatientFollowUpAction;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Patient;
use App\Models\PatientFollowUpTask;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class CreatePatientFollowUpActionTest extends TestCase
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

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('clinic_id');
            $table->date('appointment_date')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_follow_up_tasks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->uuid('plan_uuid')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_at');
            $table->string('priority');
            $table->string('contact_method');
            $table->boolean('send_sms')->default(false);
            $table->boolean('sms_consent_confirmed')->default(false);
            $table->string('status');
            $table->string('outcome')->nullable();
            $table->text('result_notes')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('sms_sent_at')->nullable();
            $table->dateTime('sms_attempted_at')->nullable();
            $table->unsignedSmallInteger('sms_attempts')->default(0);
            $table->string('last_sms_error')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_creates_a_grouped_multi_step_plan_and_only_enables_sms_with_recorded_consent(): void
    {
        $doctor = new Doctor();
        $doctor->id = 12;

        $actor = new User();
        $actor->id = 7;

        $patient = Patient::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Test Patient',
            'phone' => '09120000000',
        ]);

        DB::table('addresses')->insert([
            'id' => 41,
            'doctor_id' => $doctor->id,
            'name' => 'Test Clinic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointmentId = DB::table('appointments')->insertGetId([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'clinic_id' => 41,
            'appointment_date' => today()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tasks = app(CreatePatientFollowUpAction::class)->handle($doctor, $actor, [
            'patient_id' => $patient->id,
            'appointment_id' => $appointmentId,
            'due_at' => now()->addDay()->toDateTimeString(),
            'priority' => 'normal',
            'contact_method' => 'call',
            'steps' => [
                [
                    'title' => 'تماس اولیه',
                    'days' => 3,
                    'contact_method' => 'call',
                    'send_sms' => true,
                    'sms_consent_confirmed' => false,
                ],
                [
                    'title' => 'یادآوری آزمایش',
                    'days' => 7,
                    'contact_method' => 'sms',
                    'send_sms' => true,
                    'sms_consent_confirmed' => true,
                ],
            ],
        ], [41]);

        $this->assertCount(2, $tasks);
        $this->assertSame($tasks[0]->plan_uuid, $tasks[1]->plan_uuid);
        $this->assertSame(41, (int) $tasks[0]->clinic_id);
        $this->assertFalse($tasks[0]->send_sms);
        $this->assertTrue($tasks[1]->send_sms);
        $this->assertTrue($tasks[1]->sms_consent_confirmed);
        $this->assertSame('تماس اولیه', $tasks[0]->title);
        $this->assertSame('یادآوری آزمایش', $tasks[1]->title);
    }

    public function test_it_rejects_patients_without_a_visit_in_an_accessible_clinic(): void
    {
        $doctor = new Doctor();
        $doctor->id = 22;

        $actor = new User();
        $actor->id = 8;

        $patient = Patient::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Out of scope',
        ]);

        DB::table('appointments')->insert([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'clinic_id' => 99,
            'appointment_date' => today()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        app(CreatePatientFollowUpAction::class)->handle($doctor, $actor, [
            'patient_id' => $patient->id,
            'due_at' => now()->addDay()->toDateTimeString(),
            'priority' => 'normal',
            'contact_method' => 'call',
            'steps' => [[
                'title' => 'تماس',
                'days' => 1,
                'contact_method' => 'call',
            ]],
        ], [41]);
    }

    public function test_it_rejects_a_follow_up_when_its_clinic_is_no_longer_active(): void
    {
        $doctor = new Doctor();
        $doctor->id = 23;
        $actor = new User();
        $actor->id = 9;

        DB::table('addresses')->insert([
            'id' => 42,
            'doctor_id' => 23,
            'name' => 'Inactive Clinic',
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $patient = Patient::query()->create(['doctor_id' => 23, 'name' => 'Patient']);
        $appointmentId = DB::table('appointments')->insertGetId([
            'doctor_id' => 23,
            'patient_id' => $patient->id,
            'clinic_id' => 42,
            'appointment_date' => today()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            app(CreatePatientFollowUpAction::class)->handle($doctor, $actor, [
                'patient_id' => $patient->id,
                'appointment_id' => $appointmentId,
                'due_at' => now()->addDay()->toDateTimeString(),
                'priority' => 'normal',
                'contact_method' => 'call',
                'steps' => [[
                    'title' => 'تماس',
                    'days' => 1,
                    'contact_method' => 'call',
                ]],
            ], [42]);
            $this->fail('An inactive clinic must not receive a new patient follow-up.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseCount('patient_follow_up_tasks', 0);
        }
    }

    public function test_it_rejects_an_appointment_linked_to_a_different_patient(): void
    {
        $doctor = new Doctor();
        $doctor->id = 24;
        $actor = new User();
        $actor->id = 10;

        DB::table('addresses')->insert([
            'id' => 43,
            'doctor_id' => 24,
            'name' => 'Test Clinic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $requestedPatient = Patient::query()->create(['doctor_id' => 24, 'name' => 'Requested patient']);
        $otherPatient = Patient::query()->create(['doctor_id' => 24, 'name' => 'Other patient']);
        $appointmentId = DB::table('appointments')->insertGetId([
            'doctor_id' => 24,
            'patient_id' => $otherPatient->id,
            'clinic_id' => 43,
            'appointment_date' => today()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            app(CreatePatientFollowUpAction::class)->handle($doctor, $actor, [
                'patient_id' => $requestedPatient->id,
                'appointment_id' => $appointmentId,
                'due_at' => now()->addDay()->toDateTimeString(),
                'priority' => 'normal',
                'contact_method' => 'call',
                'steps' => [[
                    'title' => 'تماس',
                    'days' => 1,
                    'contact_method' => 'call',
                ]],
            ], [43]);
            $this->fail('An appointment belonging to another patient must be rejected.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('patient_follow_up_tasks', 0);
        }
    }

}