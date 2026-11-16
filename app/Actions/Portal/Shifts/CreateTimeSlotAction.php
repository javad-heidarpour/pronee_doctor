<?php

namespace App\Actions\Portal\Shifts;

use App\Models\Doctor;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;

class CreateTimeSlotAction
{
    public function handle(Doctor $doctor, array $data): bool
    {
        return DB::transaction(function () use ($doctor, $data) {
            $overlap = TimeSlot::query()
                ->overlapping((int) $doctor->id, $data['date'], $data['start_time'], $data['end_time'])
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
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
        });
    }
}
