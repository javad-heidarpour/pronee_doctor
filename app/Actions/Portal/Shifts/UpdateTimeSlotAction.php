<?php

namespace App\Actions\Portal\Shifts;

use App\Models\Doctor;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;

class UpdateTimeSlotAction
{
    public function handle(Doctor $doctor, TimeSlot $slot, array $data): bool
    {
        return DB::transaction(function () use ($doctor, $slot, $data) {
            $lockedSlot = TimeSlot::query()
                ->whereKey($slot->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedSlot || (int) $lockedSlot->doctor_id !== (int) $doctor->id || $lockedSlot->isBooked()) {
                return false;
            }

            $overlap = TimeSlot::query()
                ->overlapping(
                    (int) $doctor->id,
                    $data['date'],
                    $data['start_time'],
                    $data['end_time'],
                    (int) $lockedSlot->id
                )
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
                return false;
            }

            $lockedSlot->update([
                'clinic_id' => $data['clinic_id'],
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
            ]);

            return true;
        });
    }
}
