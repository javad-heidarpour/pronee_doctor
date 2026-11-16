<?php

namespace App\Actions\Portal\Shifts;

use App\Models\Doctor;
use App\Models\TimeSlot;

class UpdateTimeSlotAction
{
    public function handle(Doctor $doctor, TimeSlot $slot, array $data): bool
    {
        $duplicate = TimeSlot::query()
            ->where('doctor_id', $doctor->id)
            ->whereKeyNot($slot->id)
            ->where('clinic_id', $data['clinic_id'])
            ->where('date', $data['date'])
            ->where('start_time', $data['start_time'])
            ->exists();

        if ($duplicate) {
            return false;
        }

        $slot->update($data);

        return true;
    }
}