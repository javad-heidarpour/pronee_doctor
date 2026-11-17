<?php

declare(strict_types=1);

namespace Tests\Unit\Payments;

use App\Actions\Payments\CancelFailedAppointmentAction;
use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Models\TimeSlot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class CancelFailedAppointmentActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->string('payment_status')->nullable();
            $table->unsignedBigInteger('time_slot_id')->nullable();
            $table->timestamp('reservation_expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('time_slots', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->timestamps();
        });
    }

    public function test_it_cancels_the_reservation_and_releases_the_slot_when_no_other_payment_exists(): void
    {
        $slot = $this->slot();
        $appointment = $this->appointment($slot, 'pending');
        $payment = $this->payment($appointment, 'initiated');

        $result = app(CancelFailedAppointmentAction::class)->handle($payment);

        $this->assertSame('cancelled', $result);
        $this->assertDatabaseHas('appointment_payments', [
            'id' => $payment->id,
            'status' => 'failed',
        ]);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
            'payment_status' => 'failed',
            'reservation_expires_at' => null,
        ]);
        $this->assertSame('available', $slot->fresh()->status);
    }

    public function test_it_keeps_the_reservation_when_another_payment_attempt_is_unresolved(): void
    {
        $slot = $this->slot();
        $appointment = $this->appointment($slot, 'pending');
        $payment = $this->payment($appointment, 'initiated');
        $this->payment($appointment, 'pending');

        $result = app(CancelFailedAppointmentAction::class)->handle($payment);

        $this->assertSame('waiting', $result);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'pending',
        ]);
        $this->assertSame('booked', $slot->fresh()->status);
    }

    public function test_it_preserves_a_successful_sibling_payment_and_confirms_the_appointment(): void
    {
        $slot = $this->slot();
        $appointment = $this->appointment($slot, 'pending');
        $payment = $this->payment($appointment, 'initiated');
        $this->payment($appointment, 'paid');

        $result = app(CancelFailedAppointmentAction::class)->handle($payment);

        $this->assertSame('already_paid', $result);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'reservation_expires_at' => null,
        ]);
        $this->assertSame('booked', $slot->fresh()->status);
    }

    public function test_it_does_not_cancel_a_manually_confirmed_appointment(): void
    {
        $slot = $this->slot();
        $appointment = $this->appointment($slot, 'confirmed');
        $payment = $this->payment($appointment, 'initiated');

        $result = app(CancelFailedAppointmentAction::class)->handle($payment);

        $this->assertSame('not_cancellable', $result);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);
        $this->assertSame('booked', $slot->fresh()->status);
    }

    public function test_it_does_not_change_an_already_paid_attempt(): void
    {
        $slot = $this->slot();
        $appointment = $this->appointment($slot, 'confirmed');
        $payment = $this->payment($appointment, 'paid');

        $result = app(CancelFailedAppointmentAction::class)->handle($payment);

        $this->assertSame('already_paid', $result);
        $this->assertDatabaseHas('appointment_payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
    }

    private function slot(): TimeSlot
    {
        return TimeSlot::query()->create(['status' => 'booked']);
    }

    private function appointment(TimeSlot $slot, string $status): Appointment
    {
        return Appointment::query()->create([
            'status' => $status,
            'time_slot_id' => $slot->id,
            'reservation_expires_at' => now()->addMinutes(10),
        ]);
    }

    private function payment(Appointment $appointment, string $status): AppointmentPayment
    {
        return AppointmentPayment::query()->create([
            'appointment_id' => $appointment->id,
            'status' => $status,
        ]);
    }
}
