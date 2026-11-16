<?php

namespace App\Actions\Portal\Shifts;

use App\Models\TimeSlot;

class DeleteTimeSlotAction
{
    public function handle(TimeSlot $slot): void
    {
        $slot->delete();
    }
}