<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Patient\CreatePatientFollowUpAction;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientFollowUpTask;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('clinic_id');
            $table->date('appointment_date')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_treatment_plans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('status')->default('proposed');
            $table->boolean('follow_up_sms_enabled')->default(false);
            $table->boolean('follow_up_sms_consent_confirmed')->default(false);
            $table->timestamp('follow_up_sms_consent_revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_follow_up_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('title');
            $table->string('category');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('default_days')->default(3);
            $table->string('contact_method');
            $table->boolean('send_sms')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('patient_follow_up_tasks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('treatment_plan_id')->nullable();
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

    public function test_it_links_a_treatment_follow_up_only_to_the_same_patient_doctor_and_clinic(): void
    {
        $doctor = new Doctor();
        $doctor->id = 32;
        $actor = new User();
        $actor->id = 9;
        $patient = Patient::query()->create(['doctor_id' => 32, 'name' => 'Treatment Patient']);

        DB::table('appointments')->insert([
            'doctor_id' => 32,
            'patient_id' => $patient->id,
            'clinic_id' => 41,
            'appointment_date' => today()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $planId = DB::table('patient_treatment_plans')->insertGetId([
            'doctor_id' => 32,
            'clinic_id' => 41,
            'patient_id' => $patient->id,
            'follow_up_sms_enabled' => true,
            'follow_up_sms_consent_confirmed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tasks = app(CreatePatientFollowUpAction::class)->handle($doctor, $actor, [
            'patient_id' => $patient->id,
            'treatment_plan_id' => $planId,
            'due_at' => now()->addDay()->toDateTimeString(),
            'priority' => 'normal',
            'contact_method' => 'call',
            'steps' => [[
                'title' => 'پیگیری درمان',
                'days' => 2,
                'contact_method' => 'call',
                'send_sms' => true,
                'sms_consent_confirmed' => true,
            ]],
        ], [41]);

        $this->assertSame($planId, (int) $tasks->first()->treatment_plan_id);
        $this->assertTrue($tasks->first()->send_sms);
    }

    public function test_it_disables_follow_up_sms_when_treatment_plan_consent_was_revoked(): void
    {
        $doctor = new Doctor();
        $doctor->id = 33;
        $actor = new User();
        $actor->id = 10;
        $patient = Patient::query()->create(['doctor_id' => 33, 'name' => 'Consent Patient']);

        DB::table('appointments')->insert([
            'doctor_id' => 33,
            'patient_id' => $patient->id,
            'clinic_id' => 41,
            'appointment_date' => today()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $planId = DB::table('patient_treatment_plans')->insertGetId([
            'doctor_id' => 33,
            'clinic_id' => 41,
            'patient_id' => $patient->id,
            'follow_up_sms_enabled' => true,
            'follow_up_sms_consent_confirmed' => true,
            'follow_up_sms_consent_revoked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tasks = app(CreatePatientFollowUpAction::class)->handle($doctor, $actor, [
            'patient_id' => $patient->id,
            'treatment_plan_id' => $planId,
            'due_at' => now()->addDay()->toDateTimeString(),
            'priority' => 'normal',
            'contact_method' => 'call',
            'steps' => [[
                'title' => 'پیگیری پس از درمان',
                'days' => 1,
                'contact_method' => 'sms',
                'send_sms' => true,
                'sms_consent_confirmed' => true,
            ]],
        ], [41]);

        $this->assertFalse($tasks->first()->send_sms);
    }

    public function test_it_uses_template_defaults_when_creating_a_single_step_follow_up(): void
    {
        $doctor = new Doctor();
        $doctor->id = 34;
        $actor = new User();
        $actor->id = 11;
        $patient = Patient::query()->create(['doctor_id' => 34, 'name' => 'Template Patient']);

        DB::table('appointments')->insert([
            'doctor_id' => 34,
            'patient_id' => $patient->id,
            'clinic_id' => 41,
            'appointment_date' => today()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $templateId = DB::table('patient_follow_up_templates')->insertGetId([
            'doctor_id' => 34,
            'title' => 'پیگیری پیش‌فرض آزمایش',
            'category' => 'lab',
            'default_days' => 7,
            'contact_method' => 'review',
            'send_sms' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $baseDate = now()->addDay()->startOfMinute();

        $tasks = app(CreatePatientFollowUpAction::class)->handle($doctor, $actor, [
            'patient_id' => $patient->id,
            'template_id' => $templateId,
            'due_at' => $baseDate->toDateTimeString(),
            'priority' => 'normal',
            'contact_method' => 'call',
        ], [41]);

        $this->assertCount(1, $tasks);
        $this->assertSame('پیگیری پیش‌فرض آزمایش', $tasks->first()->title);
        $this->assertSame('review', $tasks->first()->contact_method);
        $this->assertSame($baseDate->copy()->addDays(7)->toDateTimeString(), $tasks->first()->due_at->toDateTimeString());
        $this->assertSame($templateId, (int) $tasks->first()->template_id);
    }

    public function test_it_rejects_inactive_templates_for_new_follow_ups(): void
    {
        $doctor = new Doctor();
        $doctor->id = 35;
        $actor = new User();
        $actor->id = 12;
        $patient = Patient::query()->create(['doctor_id' => 35, 'name' => 'Inactive Template Patient']);

        DB::table('appointments')->insert([
            'doctor_id' => 35,
            'patient_id' => $patient->id,
            'clinic_id' => 41,
            'appointment_date' => today()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $templateId = DB::table('patient_follow_up_templates')->insertGetId([
            'doctor_id' => 35,
            'title' => 'الگوی غیرفعال',
            'category' => 'general',
            'default_days' => 3,
            'contact_method' => 'call',
            'send_sms' => false,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(ModelNotFoundException::class);

        app(CreatePatientFollowUpAction::class)->handle($doctor, $actor, [
            'patient_id' => $patient->id,
            'template_id' => $templateId,
            'due_at' => now()->addDay()->toDateTimeString(),
            'priority' => 'normal',
            'contact_method' => 'call',
        ], [41]);
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
}