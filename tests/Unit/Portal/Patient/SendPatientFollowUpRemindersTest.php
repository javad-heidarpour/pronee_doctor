<?php

declare(strict_types=1);

namespace Tests\\Unit\\Portal\\Patient;

use App\\Contracts\\Sms\\SmsGateway;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;
use Mockery;
use Tests\\TestCase;

final class SendPatientFollowUpRemindersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_treatment_plans', function (Blueprint $table): void {
            $table->id();
            $table->boolean('follow_up_sms_enabled')->default(false);
            $table->boolean('follow_up_sms_consent_confirmed')->default(false);
            $table->timestamp('follow_up_sms_consent_revoked_at')->nullable();
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
            $table->string('contact_method')->nullable();
            $table->boolean('send_sms')->default(false);
            $table->boolean('sms_consent_confirmed')->default(false);
            $table->string('status');
            $table->string('outcome')->nullable();
            $table->text('result_notes')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('sms_sent_at')->nullable();
            $table->dateTime('sms_attempted_at')->nullable();
            $table->unsignedSmallInteger('sms_attempts')->default(0);
            $table->string('last_sms_error', 500)->nullable();
            $table->timestamps();
        });

        config([
            'services.ghasedak.api_key' => 'test-key',
            'services.ghasedak.patient_follow_up_template' => 'patient-follow-up',
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['patient_follow_up_tasks', 'patient_treatment_plans', 'patients'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_it_never_sends_a_follow_up_sms_without_task_level_consent(): void
    {
        $patientId = $this->createPatient();
        $taskId = $this->createTask($patientId, false);

        $sms = Mockery::mock(SmsGateway::class);
        $sms->shouldNotReceive('sendTemplate');
        $this->app->instance(SmsGateway::class, $sms);

        $this->artisan('patients:send-follow-up-reminders')->assertExitCode(0);

        $this->assertDatabaseHas('patient_follow_up_tasks', [
            'id' => $taskId,
            'sms_attempts' => 0,
            'sms_sent_at' => null,
        ]);
    }

    public function test_it_never_sends_a_treatment_follow_up_after_consent_revocation(): void
    {
        $patientId = $this->createPatient();
        $planId = DB::table('patient_treatment_plans')->insertGetId([
            'follow_up_sms_enabled' => true,
            'follow_up_sms_consent_confirmed' => true,
            'follow_up_sms_consent_revoked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $taskId = $this->createTask($patientId, true, $planId);

        $sms = Mockery::mock(SmsGateway::class);
        $sms->shouldNotReceive('sendTemplate');
        $this->app->instance(SmsGateway::class, $sms);

        $this->artisan('patients:send-follow-up-reminders')->assertExitCode(0);

        $this->assertDatabaseHas('patient_follow_up_tasks', [
            'id' => $taskId,
            'sms_attempts' => 0,
            'sms_sent_at' => null,
        ]);
    }

    public function test_it_sends_a_due_follow_up_once_when_consent_is_confirmed(): void
    {
        $patientId = $this->createPatient();
        $taskId = $this->createTask($patientId, true);

        $sms = Mockery::mock(SmsGateway::class);
        $sms->shouldReceive('sendTemplate')
            ->once()
            ->with(
                '09120000000',
                'patient-follow-up',
                Mockery::type('array'),
                'followup-' . $taskId
            )
            ->andReturn([]);
        $this->app->instance(SmsGateway::class, $sms);

        $this->artisan('patients:send-follow-up-reminders')->assertExitCode(0);

        $this->assertDatabaseHas('patient_follow_up_tasks', [
            'id' => $taskId,
            'sms_attempts' => 1,
        ]);
        $this->assertNotNull(DB::table('patient_follow_up_tasks')->where('id', $taskId)->value('sms_sent_at'));
    }

    private function createPatient(): int
    {
        return DB::table('patients')->insertGetId([
            'name' => 'Test Patient',
            'phone' => '09120000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTask(int $patientId, bool $consent, ?int $treatmentPlanId = null): int
    {
        return DB::table('patient_follow_up_tasks')->insertGetId([
            'doctor_id' => 1,
            'clinic_id' => 1,
            'patient_id' => $patientId,
            'treatment_plan_id' => $treatmentPlanId,
            'title' => 'پیگیری بیمار',
            'due_at' => now()->subMinute(),
            'priority' => 'normal',
            'contact_method' => 'sms',
            'send_sms' => true,
            'sms_consent_confirmed' => $consent,
            'status' => 'pending',
            'sms_attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
