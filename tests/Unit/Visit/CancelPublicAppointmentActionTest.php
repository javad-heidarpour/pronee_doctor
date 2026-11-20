<?php

declare(strict_types=1);

namespace Tests\Unit\Visit;

use App\Actions\Visit\CancelPublicAppointmentAction;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class CancelPublicAppointmentActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-20 08:00:00');

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // Lifecycle SMS resolves the clinic relation; keep the minimal relation table in this isolated test schema.
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->softDeletes();
        });

        Schema::create('time_slots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('time_slot_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('patient_name');
            $table->string('patient_phone');
            $table->string('patient_national_code')->nullable();
            $table->string('status');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->timestamps();
        });

        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->string('status');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_patient_can_cancel_a_future_unpaid_appointment_and_release_the_slot(): void
    {
        [$appointment, $slot] = $this->appointment('pending', 'booked');

        $result = app(CancelPublicAppointmentAction::class)->handle($appointment->id, '09120000000');

        $this->assertSame('cancelled', $result);
        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertSame('available', $slot->fresh()->status);
    }

    public function test_cancellation_requires_the_phone_number_registered_for_the_appointment(): void
    {
        [$appointment, $slot] = $this->appointment('confirmed', 'booked');

        try {
            app(CancelPublicAppointmentAction::class)->handle($appointment->id, '09121111111');
            $this->fail('A mismatched mobile number must not cancel the appointment.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('patient_phone', $exception->errors());
        }

        $this->assertSame('confirmed', $appointment->fresh()->status);
        $this->assertSame('booked', $slot->fresh()->status);
    }

    public function test_paid_appointment_cannot_be_cancelled_through_the_public_flow(): void
    {
        [$appointment, $slot] = $this->appointment('confirmed', 'booked');
        $appointment->payments()->create(['status' => 'paid']);

        try {
            app(CancelPublicAppointmentAction::class)->handle($appointment->id, '09120000000');
            $this->fail('A paid appointment must use the refund workflow.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('patient_phone', $exception->errors());
        }

        $this->assertSame('confirmed', $appointment->fresh()->status);
        $this->assertSame('booked', $slot->fresh()->status);
    }

    public function test_completed_appointment_cannot_be_cancelled(): void
    {
        [$appointment, $slot] = $this->appointment('completed', 'booked');

        try {
            app(CancelPublicAppointmentAction::class)->handle($appointment->id, '09120000000');
            $this->fail('A completed appointment must remain immutable.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('patient_phone', $exception->errors());
        }

        $this->assertSame('completed', $appointment->fresh()->status);
        $this->assertSame('booked', $slot->fresh()->status);
    }

    public function test_past_appointment_cannot_be_cancelled_online(): void
    {
        [$appointment, $slot] = $this->appointment('confirmed', 'booked', '2026-11-19', '09:00:00');

        try {
            app(CancelPublicAppointmentAction::class)->handle($appointment->id, '09120000000');
            $this->fail('A past appointment must not be cancelled through the public flow.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('patient_phone', $exception->errors());
        }

        $this->assertSame('confirmed', $appointment->fresh()->status);
        $this->assertSame('booked', $slot->fresh()->status);
    }

    public function test_repeated_cancellation_is_idempotent(): void
    {
        [$appointment, $slot] = $this->appointment('cancelled', 'available');

        $result = app(CancelPublicAppointmentAction::class)->handle($appointment->id, '09120000000');

        $this->assertSame('already_cancelled', $result);
        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertSame('available', $slot->fresh()->status);
    }

    public function test_cancellation_does_not_release_a_slot_used_by_another_active_appointment(): void
    {
        [$appointment, $slot] = $this->appointment('confirmed', 'booked');
        Appointment::query()->create([
            'doctor_id' => $appointment->doctor_id,
            'clinic_id' => $appointment->clinic_id,
            'time_slot_id' => $slot->id,
            'patient_name' => 'Another patient',
            'patient_phone' => '09123333333',
            'status' => 'confirmed',
            'appointment_date' => '2026-11-25',
            'appointment_time' => '09:00:00',
        ]);

        app(CancelPublicAppointmentAction::class)->handle($appointment->id, '09120000000');

        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertSame('booked', $slot->fresh()->status);
    }

    private function appointment(
        string $status,
        string $slotStatus,
        string $date = '2026-11-25',
        string $time = '09:00:00'
    ): array {
        $doctor = Doctor::query()->create([
            'name' => 'Public cancellation test',
            'slug' => 'public-cancel-' . uniqid(),
        ]);

        $slot = TimeSlot::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => 10,
            'date' => $date,
            'start_time' => $time,
            'end_time' => '09:30:00',
            'status' => $slotStatus,
        ]);

        $appointment = Appointment::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => 10,
            'time_slot_id' => $slot->id,
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
            'status' => $status,
            'appointment_date' => $date,
            'appointment_time' => $time,
        ]);

        return [$appointment, $slot];
    }
}
