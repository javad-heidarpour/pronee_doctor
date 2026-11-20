<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Appointment;

use App\Actions\Portal\Appointment\UpdateAppointmentStatusAction;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\TimeSlot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class UpdateAppointmentStatusActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
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
            $table->timestamps();
        });

        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->string('status');
            $table->timestamps();
        });
    }

    public function test_cancelling_an_appointment_releases_its_slot(): void
    {
        [$appointment, $slot] = $this->bookedAppointment('booked', 'confirmed');

        $result = app(UpdateAppointmentStatusAction::class)->handle($appointment, 'cancelled');

        $this->assertSame('updated', $result);
        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertSame('available', $slot->fresh()->status);
    }

    public function test_legacy_completed_appointment_is_immutable(): void
    {
        [$appointment, $slot] = $this->bookedAppointment('booked', 'completed');

        $result = app(UpdateAppointmentStatusAction::class)->handle($appointment, 'cancelled');

        $this->assertSame('already_visited', $result);
        $this->assertSame('completed', $appointment->fresh()->status);
        $this->assertSame('booked', $slot->fresh()->status);
    }

    public function test_cancelled_appointment_cannot_be_reactivated_on_a_cancelled_slot(): void
    {
        [$appointment] = $this->bookedAppointment('cancelled', 'cancelled');

        try {
            app(UpdateAppointmentStatusAction::class)->handle($appointment, 'pending');
            $this->fail('A cancelled slot must not be re-booked by reactivating an old appointment.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }
    }

    /**
     * @return array{Appointment, TimeSlot}
     */
    private function bookedAppointment(string $slotStatus, string $appointmentStatus): array
    {
        $doctor = Doctor::query()->create([
            'name' => 'Appointment Status Test',
            'slug' => 'appointment-status-test-' . uniqid(),
        ]);

        $slot = TimeSlot::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => 10,
            'date' => '2026-11-25',
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
            'status' => $slotStatus,
        ]);

        $appointment = Appointment::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => 10,
            'time_slot_id' => $slot->id,
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
            'status' => $appointmentStatus,
            'appointment_date' => '2026-11-25',
            'appointment_time' => '09:00:00',
        ]);

        return [$appointment, $slot];
    }
}
