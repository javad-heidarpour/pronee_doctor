<?php

namespace App\Actions\Portal\Shifts;

use App\Models\Doctor;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;

class DeleteTimeSlotAction
{
    public function handle(TimeSlot $slot): bool
    {
        return DB::transaction(function () use ($slot) {
            Doctor::query()->whereKey($slot->doctor_id)->lockForUpdate()->firstOrFail();

            $lockedSlot = TimeSlot::query()
                ->whereKey($slot->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedSlot || $lockedSlot->isBooked()) {
                return false;
            }

            return (bool) $lockedSlot->delete();
        });
    }
}
