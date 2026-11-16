<?php

namespace App\Actions\Portal\Shifts;

use App\Models\TimeSlot;

class ToggleTimeSlotAction
{
    public function handle(TimeSlot $slot): string
    {
        $newStatus = $slot->status === 'available'
            ? 'cancelled'
            : 'available';

        $slot->update(['status' => $newStatus]);

        return $newStatus;
    }
}