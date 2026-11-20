<?php

declare(strict_types=1);

namespace Tests\Unit\Sms;

use App\Actions\Sms\SendAppointmentLifecycleSmsAction;
use App\Actions\Sms\SendAppointmentReminderSmsAction;
use App\Contracts\Sms\SmsGateway;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

final class AppointmentRescheduleNotificationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-20 08:00:00');

        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('name')->nullable();
            $table->text('full_address')->nullable();
            $table->string('phone')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('time_slot_id')->nullable();
            $table->string('patient_name');
            $table->string('patient_phone');
            $table->string('status');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->string('type', 80);
            $table->string('status', 30)->default('pending');
            $table->string('provider', 60)->nullable();
            $table->string('deduplication_key')->nullable()->unique();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        foreach (['sms_logs', 'appointments', 'addresses', 'cities'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_reminder_can_be_sent_again_after_the_appointment_schedule_changes(): void
    {
        config([
            'services.ghasedak.api_key' => 'test-api-key',
            'services.ghasedak.appointment_reminder_template' => 'AppointmentReminderSMS',
        ]);

        $appointment = $this->appointment('confirmed');
        $references = [];

        $gateway = Mockery::mock(SmsGateway::class);
        $gateway->shouldReceive('sendTemplate')
            ->twice()
            ->andReturnUsing(function (
                string $mobile,
                string $template,
                array $inputs,
                string $reference
            ) use (&$references): array {
                $this->assertSame('09120000000', $mobile);
                $this->assertSame('AppointmentReminderSMS', $template);
                $this->assertCount(3, $inputs);
                $references[] = $reference;

                return ['success' => true];
            });
        $this->app->instance(SmsGateway::class, $gateway);

        $action = app(SendAppointmentReminderSmsAction::class);

        $this->assertSame('sent', $action->handle($appointment));
        $this->assertSame('already_attempted', $action->handle($appointment));

        $appointment->update([
            'appointment_date' => '2026-11-22',
            'appointment_time' => '11:00:00',
        ]);

        $this->assertSame('sent', $action->handle($appointment->fresh()));
        $this->assertSame('already_attempted', $action->handle($appointment->fresh()));

        $this->assertSame([
            'reminder-' . $appointment->id . '-2026-11-21-1000',
            'reminder-' . $appointment->id . '-2026-11-22-1100',
        ], $references);
        $this->assertDatabaseCount('sms_logs', 2);
    }

    public function test_reschedule_notification_is_sent_for_contacted_appointments(): void
    {
        config([
            'services.ghasedak.api_key' => 'test-api-key',
            'services.ghasedak.appointment_update_template' => 'AppointmentUpdateSMS',
        ]);

        $appointment = $this->appointment('contacted');
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
        $this->assertDatabaseCount('sms_logs', 1);
        $this->assertDatabaseHas('sms_logs', [
            'appointment_id' => $appointment->id,
            'type' => 'appointment_updated',
            'status' => 'sent',
        ]);
    }

    private function appointment(string $status): Appointment
    {
        $cityId = DB::table('cities')->insertGetId([
            'name' => 'بجنورد',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => 1,
            'city_id' => $cityId,
            'name' => 'مطب آزمایشی',
            'full_address' => 'نشانی آزمایشی',
            'phone' => '05100000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointmentId = DB::table('appointments')->insertGetId([
            'doctor_id' => 1,
            'clinic_id' => $clinicId,
            'patient_name' => 'بیمار آزمایشی',
            'patient_phone' => '09120000000',
            'status' => $status,
            'appointment_date' => '2026-11-21',
            'appointment_time' => '10:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Appointment::query()->findOrFail($appointmentId);
    }
}
