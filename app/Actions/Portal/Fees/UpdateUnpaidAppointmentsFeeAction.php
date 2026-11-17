<?php

declare(strict_types=1);

namespace App\Actions\Portal\Fees;

use App\Models\Appointment;

/**
 * Applies a newly configured consultation fee only to unpaid appointments.
 *
 * A payment that is pending, initiated, or paid freezes the appointment fee
 * so that an in-flight checkout or completed transaction cannot be repriced.
 */
final class UpdateUnpaidAppointmentsFeeAction
{
    public function handle(int $newFee, int $doctorId): int
    {
        return Appointment::query()
            ->where('doctor_id', $doctorId)
            ->where('status', 'pending')
            ->whereDoesntHave('payments', function ($query): void {
                $query->whereIn('status', ['pending', 'initiated', 'paid']);
            })
            ->update(['fee_rial' => $newFee]);
    }
}
