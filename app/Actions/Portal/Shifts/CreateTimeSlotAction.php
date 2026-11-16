<?php

namespace App\Actions\Portal\Shifts;

use App\Models\Doctor;
use App\Models\TimeSlot;

class CreateTimeSlotAction
{
    public function handle(Doctor $doctor, array $data): bool
    {
        $duplicate = TimeSlot::query()
            ->where('doctor_id', $doctor->id)
            ->where('clinic_id', $data['clinic_id'])
            ->where('date', $data['date'])
            ->where('start_time', $data['start_time'])
            ->exists();

        if ($duplicate) {
            return false;
        }

        TimeSlot::create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $data['clinic_id'],
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => 'available',
        ]);

        return true;
    }
}