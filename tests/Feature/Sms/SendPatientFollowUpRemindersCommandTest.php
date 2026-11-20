<?php

declare(strict_types=1);

namespace Tests\Feature\Sms;

use App\Contracts\Sms\SmsGateway;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientFollowUpTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

final class SendPatientFollowUpRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_only_due_followups_with_valid_current_sms_consent(): void
    {
        config([
            'services.ghasedak.api_key' => 'test-api-key',
            'services.ghasedak.patient_follow_up_template' => 'PatientFollowUpSMS',
        ]);

        [$doctor, $clinicId, $patient] = $this->doctorClinicAndPatient();

        $allowed = $this->followUp($doctor, $clinicId, $patient, [
            'sms_consent_confirmed' => true,
        ]);
        $this->followUp($doctor, $clinicId, $patient, [
            'sms_consent_confirmed' => false,
        ]);

        $revokedPlanId = DB::table('patient_treatment_plans')->insertGetId([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'patient_id' => $patient->id,
            'title' => 'Treatment plan',
            'total_amount' => 100000,
            'follow_up_sms_enabled' => true,
            'follow_up_sms_consent_confirmed' => true,
            'follow_up_sms_consent_revoked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $revoked = $this->followUp($doctor, $clinicId, $patient, [
            'sms_consent_confirmed' => true,
            'treatment_plan_id' => $revokedPlanId,
        ]);

        $gateway = Mockery::mock(SmsGateway::class);
        $gateway->shouldReceive('sendTemplate')
            ->once()
            ->with(
                $patient->phone,
                'PatientFollowUpSMS',
                Mockery::on(fn (array $inputs): bool => count($inputs) === 3),
                'followup-' . $allowed->id
            )
            ->andReturn(['success' => true]);
        $this->app->instance(SmsGateway::class, $gateway);

        $this->artisan('patients:send-follow-up-reminders')->assertExitCode(0);

        $this->assertNotNull($allowed->fresh()->sms_sent_at);
        $this->assertSame(1, $allowed->fresh()->sms_attempts);
        $this->assertSame(0, $revoked->fresh()->sms_attempts);
        $this->assertSame(0, PatientFollowUpTask::query()
            ->where('sms_consent_confirmed', false)
            ->sum('sms_attempts'));
    }

    public function test_it_does_not_retry_before_thirty_minutes_or_after_three_attempts(): void
    {
        config([
            'services.ghasedak.api_key' => 'test-api-key',
            'services.ghasedak.patient_follow_up_template' => 'PatientFollowUpSMS',
        ]);

        [$doctor, $clinicId, $patient] = $this->doctorClinicAndPatient();

        $this->followUp($doctor, $clinicId, $patient, [
            'sms_consent_confirmed' => true,
            'sms_attempts' => 1,
            'sms_attempted_at' => now()->subMinutes(20),
        ]);
        $this->followUp($doctor, $clinicId, $patient, [
            'sms_consent_confirmed' => true,
            'sms_attempts' => 3,
            'sms_attempted_at' => now()->subHours(2),
        ]);

        $gateway = Mockery::mock(SmsGateway::class);
        $gateway->shouldNotReceive('sendTemplate');
        $this->app->instance(SmsGateway::class, $gateway);

        $this->artisan('patients:send-follow-up-reminders')->assertExitCode(0);

        $this->assertSame(1, PatientFollowUpTask::query()->where('sms_attempts', 1)->value('sms_attempts'));
        $this->assertSame(3, PatientFollowUpTask::query()->where('sms_attempts', 3)->value('sms_attempts'));
    }

    private function doctorClinicAndPatient(): array
    {
        $doctor = Doctor::factory()->create([
            'family' => 'آزمایشی',
            'slug' => 'follow-up-reminder-test',
        ]);

        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctor->id,
            'name' => 'Follow-up test clinic',
            'full_address' => 'Test address',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $patient = Patient::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Follow-up patient',
            'phone' => '09120000000',
        ]);

        return [$doctor, $clinicId, $patient];
    }

    private function followUp(Doctor $doctor, int $clinicId, Patient $patient, array $overrides = []): PatientFollowUpTask
    {
        return PatientFollowUpTask::query()->create(array_merge([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'patient_id' => $patient->id,
            'title' => 'Follow-up call',
            'due_at' => now()->subMinute(),
            'priority' => 'normal',
            'contact_method' => 'call',
            'send_sms' => true,
            'sms_consent_confirmed' => true,
            'status' => 'pending',
            'sms_attempts' => 0,
        ], $overrides));
    }
}
