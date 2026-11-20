<?php

declare(strict_types=1);

namespace Tests\Unit\Visit;

use App\Actions\Visit\ReschedulePublicAppointmentAction;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ReschedulePublicAppointmentActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-20 08:00:00');

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
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
            $table->timestamp('reservation_expires_at')->nullable();
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

    public function test_patient_can_reschedule_and_the_old_and_new_slots_are_updated_atomically(): void
    {
        [$appointment, $oldSlot, $newSlot] = $this->appointmentWithNewSlot();
        $payment = $appointment->payments()->create(['status' => 'paid']);

        $result = app(ReschedulePublicAppointmentAction::class)->handle(
            $appointment->id,
            '09120000000',
            $newSlot->id
        );

        $this->assertSame('rescheduled', $result);
        $this->assertSame($newSlot->id, $appointment->fresh()->time_slot_id);
        $this->assertSame('2026-11-27', $appointment->fresh()->appointment_date->format('Y-m-d'));
        $this->assertSame('10:00:00', $appointment->fresh()->appointment_time);
        $this->assertSame('available', $oldSlot->fresh()->status);
        $this->assertSame('booked', $newSlot->fresh()->status);
        $this->assertDatabaseHas('appointment_payments', [
            'id' => $payment->id,
            'appointment_id' => $appointment->id,
            'status' => 'paid',
        ]);
    }

    public function test_rescheduling_requires_the_registered_patient_phone(): void
    {
        [$appointment, $oldSlot, $newSlot] = $this->appointmentWithNewSlot();

        $this->assertValidationFailure(
            fn () => app(ReschedulePublicAppointmentAction::class)->handle(
                $appointment->id,
                '09121111111',
                $newSlot->id
            ),
            'patient_phone'
        );

        $this->assertSame($oldSlot->id, $appointment->fresh()->time_slot_id);
        $this->assertSame('booked', $oldSlot->fresh()->status);
        $this->assertSame('available', $newSlot->fresh()->status);
    }

    public function test_rescheduling_rejects_a_slot_that_is_no_longer_available(): void
    {
        [$appointment, $oldSlot, $newSlot] = $this->appointmentWithNewSlot();
        $newSlot->update(['status' => 'booked']);

        $this->assertValidationFailure(
            fn () => app(ReschedulePublicAppointmentAction::class)->handle(
                $appointment->id,
                '09120000000',
                $newSlot->id
            ),
            'new_time_slot_id'
        );

        $this->assertSame($oldSlot->id, $appointment->fresh()->time_slot_id);
        $this->assertSame('booked', $oldSlot->fresh()->status);
    }

    public function test_rescheduling_rejects_a_slot_from_another_clinic(): void
    {
        [$appointment, $oldSlot, $newSlot, $doctor] = $this->appointmentWithNewSlot();
        $otherClinicId = $this->clinic($doctor, 'Other clinic');

        $newSlot->update(['clinic_id' => $otherClinicId]);

        $this->assertValidationFailure(
            fn () => app(ReschedulePublicAppointmentAction::class)->handle(
                $appointment->id,
                '09120000000',
                $newSlot->id
            ),
            'new_time_slot_id'
        );

        $this->assertSame($oldSlot->id, $appointment->fresh()->time_slot_id);
    }

    public function test_rescheduling_rejects_a_past_slot(): void
    {
        [$appointment, $oldSlot, $newSlot] = $this->appointmentWithNewSlot();
        $newSlot->update([
            'date' => '2026-11-19',
            'start_time' => '09:00:00',
        ]);

        $this->assertValidationFailure(
            fn () => app(ReschedulePublicAppointmentAction::class)->handle(
                $appointment->id,
                '09120000000',
                $newSlot->id
            ),
            'new_time_slot_id'
        );

        $this->assertSame($oldSlot->id, $appointment->fresh()->time_slot_id);
    }

    public function test_expired_pending_reservation_cannot_be_rescheduled(): void
    {
        [$appointment, $oldSlot, $newSlot] = $this->appointmentWithNewSlot();
        $appointment->update([
            'status' => 'pending',
            'reservation_expires_at' => now()->subMinute(),
        ]);

        $this->assertValidationFailure(
            fn () => app(ReschedulePublicAppointmentAction::class)->handle(
                $appointment->id,
                '09120000000',
                $newSlot->id
            ),
            'new_time_slot_id'
        );

        $this->assertSame($oldSlot->id, $appointment->fresh()->time_slot_id);
        $this->assertSame('available', $newSlot->fresh()->status);
    }

    public function test_visited_appointment_cannot_be_rescheduled(): void
    {
        [$appointment, $oldSlot, $newSlot] = $this->appointmentWithNewSlot();
        $appointment->update(['status' => 'completed']);

        $this->assertValidationFailure(
            fn () => app(ReschedulePublicAppointmentAction::class)->handle(
                $appointment->id,
                '09120000000',
                $newSlot->id
            ),
            'new_time_slot_id'
        );

        $this->assertSame($oldSlot->id, $appointment->fresh()->time_slot_id);
        $this->assertSame('available', $newSlot->fresh()->status);
    }

    public function test_rescheduling_rejects_a_slot_with_another_active_appointment(): void
    {
        [$appointment, $oldSlot, $newSlot] = $this->appointmentWithNewSlot();
        Appointment::query()->create([
            'doctor_id' => $appointment->doctor_id,
            'clinic_id' => $appointment->clinic_id,
            'time_slot_id' => $newSlot->id,
            'patient_name' => 'Another patient',
            'patient_phone' => '09123333333',
            'status' => 'confirmed',
            'appointment_date' => '2026-11-27',
            'appointment_time' => '10:00:00',
        ]);

        $this->assertValidationFailure(
            fn () => app(ReschedulePublicAppointmentAction::class)->handle(
                $appointment->id,
                '09120000000',
                $newSlot->id
            ),
            'new_time_slot_id'
        );

        $this->assertSame($oldSlot->id, $appointment->fresh()->time_slot_id);
        $this->assertSame('available', $newSlot->fresh()->status);
    }

    private function appointmentWithNewSlot(): array
    {
        $doctor = Doctor::query()->create([
            'name' => 'Public rescheduling test',
            'slug' => 'public-reschedule-' . uniqid(),
            'is_active' => true,
        ]);
        $clinicId = $this->clinic($doctor, 'Main clinic');

        $oldSlot = $this->slot($doctor, $clinicId, '2026-11-25', '09:00:00', 'booked');
        $newSlot = $this->slot($doctor, $clinicId, '2026-11-27', '10:00:00', 'available');

        $appointment = Appointment::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'time_slot_id' => $oldSlot->id,
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
            'status' => 'confirmed',
            'appointment_date' => '2026-11-25',
            'appointment_time' => '09:00:00',
        ]);

        return [$appointment, $oldSlot, $newSlot, $doctor];
    }

    private function clinic(Doctor $doctor, string $name): int
    {
        return (int) DB::table('addresses')->insertGetId([
            'doctor_id' => $doctor->id,
            'name' => $name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function slot(
        Doctor $doctor,
        int $clinicId,
        string $date,
        string $time,
        string $status
    ): TimeSlot {
        return TimeSlot::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'date' => $date,
            'start_time' => $time,
            'end_time' => Carbon::parse($date . ' ' . $time)->addMinutes(30)->format('H:i:s'),
            'status' => $status,
        ]);
    }

    private function assertValidationFailure(callable $callback, string $field): void
    {
        try {
            $callback();
            $this->fail('The rescheduling request should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }
}
