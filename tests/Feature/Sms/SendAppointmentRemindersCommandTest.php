<?php

declare(strict_types=1);

namespace Tests\Feature\Sms;

use App\Contracts\Sms\SmsGateway;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

final class SendAppointmentRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_command_sends_only_for_confirmed_appointments_tomorrow(): void
    {
        config([
            'services.ghasedak.api_key' => 'test-api-key',
            'services.ghasedak.appointment_reminder_template' => 'AppointmentReminderSMS',
        ]);

        $confirmedTomorrow = $this->appointment('confirmed', now()->addDay()->toDateString());
        $this->appointment('pending', now()->addDay()->toDateString());
        $this->appointment('confirmed', now()->addDays(2)->toDateString());

        $gateway = Mockery::mock(SmsGateway::class);
        $gateway->shouldReceive('sendTemplate')
            ->once()
            ->with(
                '09120000000',
                'AppointmentReminderSMS',
                Mockery::on(fn (array $inputs): bool => count($inputs) === 3),
                'reminder-' . $confirmedTomorrow
            )
            ->andReturn(['success' => true]);
        $this->app->instance(SmsGateway::class, $gateway);

        $this->artisan('appointments:send-reminders')->assertExitCode(0);

        $this->assertDatabaseCount('sms_logs', 1);
        $this->assertDatabaseHas('sms_logs', [
            'appointment_id' => $confirmedTomorrow,
            'type' => 'appointment_reminder_day_before',
            'status' => 'sent',
        ]);
    }

    private function appointment(string $status, string $date): int
    {
        $doctor = Doctor::factory()->create(['family' => 'آزمایشی']);

        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctor->id,
            'name' => 'مطب آزمایشی',
            'full_address' => 'نشانی آزمایشی',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $slotId = DB::table('time_slots')->insertGetId([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
            'status' => $status === 'confirmed' ? 'booked' : 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('appointments')->insertGetId([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'time_slot_id' => $slotId,
            'patient_name' => 'بیمار آزمایشی',
            'patient_phone' => '09120000000',
            'status' => $status,
            'appointment_date' => $date,
            'appointment_time' => '10:00:00',
            'fee_rial' => 7000000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
