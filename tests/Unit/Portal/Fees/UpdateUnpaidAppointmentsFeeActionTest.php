<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Fees;

use App\Actions\Portal\Fees\UpdateUnpaidAppointmentsFeeAction;
use App\Models\Appointment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class UpdateUnpaidAppointmentsFeeActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('status');
            $table->unsignedBigInteger('fee_rial')->default(0);
            $table->timestamps();
        });

        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->string('status');
            $table->timestamps();
        });
    }

    public function test_it_reprices_only_pending_appointments_without_active_or_paid_payments(): void
    {
        $unpaid = $this->appointment(10);
        $pendingPayment = $this->appointment(20);
        $initiatedPayment = $this->appointment(30);
        $paidPayment = $this->appointment(40);
        $otherDoctor = $this->appointment(50, doctorId: 8);
        $cancelledAppointment = $this->appointment(60, status: 'cancelled');

        $pendingPayment->payments()->create(['status' => 'pending']);
        $initiatedPayment->payments()->create(['status' => 'initiated']);
        $paidPayment->payments()->create(['status' => 'paid']);

        $updated = app(UpdateUnpaidAppointmentsFeeAction::class)->handle(12500000, 7);

        $this->assertSame(1, $updated);
        $this->assertDatabaseHas('appointments', [
            'id' => $unpaid->id,
            'fee_rial' => 12500000,
        ]);

        foreach ([$pendingPayment, $initiatedPayment, $paidPayment, $otherDoctor, $cancelledAppointment] as $appointment) {
            $this->assertDatabaseHas('appointments', [
                'id' => $appointment->id,
                'fee_rial' => (int) $appointment->fee_rial,
            ]);
        }
    }

    private function appointment(
        int $fee,
        int $doctorId = 7,
        string $status = 'pending'
    ): Appointment {
        return Appointment::query()->create([
            'doctor_id' => $doctorId,
            'status' => $status,
            'fee_rial' => $fee,
        ]);
    }
}
