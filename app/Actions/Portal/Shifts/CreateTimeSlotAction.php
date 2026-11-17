<?php

namespace App\Actions\Portal\Shifts;

use App\DTOs\Portal\Scheduling\TimeSlotData;
use App\Models\Doctor;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;

class CreateTimeSlotAction
{
    public function handle(Doctor $doctor, TimeSlotData $data): bool
    {
        return DB::transaction(function () use ($doctor, $data) {
            Doctor::query()->whereKey($doctor->id)->lockForUpdate()->firstOrFail();

            $overlap = TimeSlot::query()
                ->overlapping($doctor->id, $data->date, $data->startTime, $data->endTime)
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
                return false;
            }

            TimeSlot::create([
                'doctor_id' => $doctor->id,
                'clinic_id' => $data->clinicId,
                'date' => $data->date,
                'start_time' => $data->startTime,
                'end_time' => $data->endTime,
                'status' => 'available',
            ]);

            return true;
        });
    }
}
