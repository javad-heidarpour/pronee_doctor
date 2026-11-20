<?php

declare(strict_types=1);

namespace Tests\Feature\Sms;

use App\Actions\Sms\SendAppointmentConfirmationSmsAction;
use App\Actions\Sms\SendAppointmentLifecycleSmsAction;
use App\Actions\Sms\SendAppointmentReminderSmsAction;
use App\Contracts\Sms\SmsGateway;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

final class AppointmentSmsNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_sms_uses_shared_gateway_and_prevents_rapid_duplicates(): void
    {
        config([
            'services.ghasedak.api_key' => 'test-api-key',
            'services.ghasedak.appointment_confirmation_template' => 'AppointmentConfirmationSMS',
        ]);

        $appointment = $this->appointment('confirmed');
        $gateway = Mockery::mock(SmsGateway::class);
        $gateway->shouldReceive('sendTemplate')
            ->once()
            ->with(
                '09120000000',
                'AppointmentConfirmationSMS',
                Mockery::on(fn (array $inputs): bool => count($inputs) === 3),
                'visit-' . $appointment->id
            )
            ->andReturn(['success' => true]);
        $this->app->instance(SmsGateway::class, $gateway);

        Cache::forget('appointment-confirmation-sms:' . $appointment->id);

        $first = app(SendAppointmentConfirmationSmsAction::class)->handle($appointment->id);
        $second = app(SendAppointmentConfirmationSmsAction::class)->handle($appointment->id);

        $this->assertSame(200, $first->getStatusCode());
        $this->assertTrue($first->getData(true)['success']);
        $this->assertSame(429, $second->getStatusCode());
        $this->assertDatabaseHas('sms_logs', [
            'appointment_id' => $appointment->id,
            'type' => 'appointment_confirmation',
            'status' => 'sent',
        ]);
        $this->assertDatabaseCount('sms_logs', 1);
    }

    public function test_day_before_reminder_is_sent_once_per_appointment(): void
    {
        config([
            'services.ghasedak.api_key' => 'test-api-key',
            'services.ghasedak.appointment_reminder_template' => 'AppointmentReminderSMS',
        ]);

        $appointment = $this->appointment('confirmed');
        $gateway = Mockery::mock(SmsGateway::class);
        $gateway->shouldReceive('sendTemplate')
            ->once()
            ->with(
                '09120000000',
                'AppointmentReminderSMS',
                Mockery::on(fn (array $inputs): bool => count($inputs) === 3),
                'reminder-' . $appointment->id
            )
            ->andReturn(['success' => true]);
        $this->app->instance(SmsGateway::class, $gateway);

        $action = app(SendAppointmentReminderSmsAction::class);

        $this->assertSame('sent', $action->handle($appointment));
        $this->assertSame('already_attempted', $action->handle($appointment));

        $this->assertDatabaseHas('sms_logs', [
            'appointment_id' => $appointment->id,
            'type' => 'appointment_reminder_day_before',
            'status' => 'sent',
        ]);
        $this->assertDatabaseCount('sms_logs', 1);
    }

    public function test_reschedule_sms_is_sent_once_for_the_current_appointment_version(): void
    {
        config([
            'services.ghasedak.api_key' => 'test-api-key',
            'services.ghasedak.appointment_update_template' => 'AppointmentUpdateSMS',
        ]);

        $appointment = $this->appointment('confirmed');
        $gateway = Mockery::mock(SmsGateway::class);
        $gateway->shouldReceive('sendTemplate')
            ->once()
            ->with(
                '09120000000',
                'AppointmentUpdateSMS',
                Mockery::on(fn (array $inputs): bool => count($inputs) === 3),
                Mockery::type('string')
            )
            ->andReturn(['success' => true]);
        $this->app->instance(SmsGateway::class, $gateway);

        $action = app(SendAppointmentLifecycleSmsAction::class);

        $this->assertSame('sent', $action->handle($appointment->id, 'updated'));
        $this->assertSame('already_attempted', $action->handle($appointment->id, 'updated'));

        $this->assertDatabaseHas('sms_logs', [
            'appointment_id' => $appointment->id,
            'type' => 'appointment_updated',
            'status' => 'sent',
        ]);
    }

    public function test_cancellation_sms_is_sent_only_for_cancelled_appointments(): void
    {
        config([
            'services.ghasedak.api_key' => 'test-api-key',
            'services.ghasedak.appointment_cancellation_template' => 'AppointmentCancellationSMS',
        ]);

        $appointment = $this->appointment('cancelled');
        $gateway = Mockery::mock(SmsGateway::class);
        $gateway->shouldReceive('sendTemplate')
            ->once()
            ->with(
                '09120000000',
                'AppointmentCancellationSMS',
                Mockery::on(fn (array $inputs): bool => count($inputs) === 3),
                Mockery::type('string')
            )
            ->andReturn(['success' => true]);
        $this->app->instance(SmsGateway::class, $gateway);

        $action = app(SendAppointmentLifecycleSmsAction::class);

        $this->assertSame('sent', $action->handle($appointment->id, 'cancelled'));
        $this->assertSame('already_attempted', $action->handle($appointment->id, 'cancelled'));

        $this->assertDatabaseHas('sms_logs', [
            'appointment_id' => $appointment->id,
            'type' => 'appointment_cancelled',
            'status' => 'sent',
        ]);
    }

    private function appointment(string $status): Appointment
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
            'date' => now()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
            'status' => $status === 'cancelled' ? 'available' : 'booked',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointmentId = DB::table('appointments')->insertGetId([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'time_slot_id' => $slotId,
            'patient_name' => 'بیمار آزمایشی',
            'patient_phone' => '09120000000',
            'status' => $status,
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00:00',
            'fee_rial' => 7000000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Appointment::query()->findOrFail($appointmentId);
    }
}
