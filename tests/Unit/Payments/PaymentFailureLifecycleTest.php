<?php

declare(strict_types=1);

namespace Tests\Unit\Payments;

use App\Actions\Payments\CancelFailedAppointmentAction;
use App\Models\Appointment;
use App\Models\AppointmentPayment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PaymentFailureLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('time_slots', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('time_slot_id')->nullable();
            $table->string('status');
            $table->string('payment_status')->nullable();
            $table->timestamp('reservation_expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('amount_rial');
            $table->string('status');
            $table->string('payment_uuid')->unique();
            $table->string('gateway_name')->default('sep');
            $table->timestamps();
        });
    }

    public function test_last_failed_attempt_cancels_pending_reservation_and_releases_slot(): void
    {
        [$appointment, $payment] = $this->reservation();
        $result = app(CancelFailedAppointmentAction::class)->handle($payment);

        $this->assertSame('cancelled', $result);
        $this->assertDatabaseHas('appointment_payments', ['id' => $payment->id, 'status' => 'failed']);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'cancelled', 'payment_status' => 'failed']);
        $this->assertDatabaseHas('time_slots', ['id' => $appointment->time_slot_id, 'status' => 'available']);
    }

    public function test_failed_attempt_does_not_cancel_reservation_while_another_attempt_is_unresolved(): void
    {
        [$appointment, $payment] = $this->reservation();
        $this->payment($appointment, 'pending');

        $result = app(CancelFailedAppointmentAction::class)->handle($payment);

        $this->assertSame('waiting', $result);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'pending']);
        $this->assertDatabaseHas('time_slots', ['id' => $appointment->time_slot_id, 'status' => 'booked']);
    }

    public function test_failed_attempt_preserves_successful_payment_and_confirms_reservation(): void
    {
        [$appointment, $payment] = $this->reservation();
        $this->payment($appointment, 'paid');

        $result = app(CancelFailedAppointmentAction::class)->handle($payment);

        $this->assertSame('already_paid', $result);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'confirmed', 'payment_status' => 'paid']);
        $this->assertDatabaseHas('time_slots', ['id' => $appointment->time_slot_id, 'status' => 'booked']);
    }

    public function test_failed_attempt_does_not_cancel_manually_confirmed_appointment(): void
    {
        [$appointment, $payment] = $this->reservation('confirmed');

        $result = app(CancelFailedAppointmentAction::class)->handle($payment);

        $this->assertSame('not_cancellable', $result);
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('time_slots', ['id' => $appointment->time_slot_id, 'status' => 'booked']);
    }

    private function reservation(string $status = 'pending'): array
    {
        $slotId = \Illuminate\Support\Facades\DB::table('time_slots')->insertGetId([
            'status' => 'booked', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $appointment = Appointment::query()->create([
            'time_slot_id' => $slotId, 'status' => $status,
            'reservation_expires_at' => now()->addMinutes(10),
        ]);
        return [$appointment, $this->payment($appointment, 'initiated')];
    }

    private function payment(Appointment $appointment, string $status): AppointmentPayment
    {
        return AppointmentPayment::query()->create([
            'appointment_id' => $appointment->id, 'amount_rial' => 7000000,
            'status' => $status, 'payment_uuid' => (string) Str::uuid(), 'gateway_name' => 'sep',
        ]);
    }
}
