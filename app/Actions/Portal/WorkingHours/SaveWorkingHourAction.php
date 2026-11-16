<?php

namespace App\Actions\Portal\WorkingHours;

use App\Models\Doctor;
use App\Models\DoctorWorkingHour;

class SaveWorkingHourAction
{
    /**
     * @return array{saved:bool,duplicate:?DoctorWorkingHour}
     */
    public function handle(
        Doctor $doctor,
        array $data,
        ?DoctorWorkingHour $workingHour = null
    ): array {
        $query = DoctorWorkingHour::query()
            ->where('doctor_id', $doctor->id)
            ->where('day_of_week', $data['day_of_week'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->with('clinic');

        if ($workingHour) {
            $query->where('id', '!=', $workingHour->id);
        }

        $duplicate = $query->first();

        if ($duplicate) {
            return ['saved' => false, 'duplicate' => $duplicate];
        }

        if ($workingHour) {
            $workingHour->update($data);
        } else {
            DoctorWorkingHour::create([
                'doctor_id' => $doctor->id,
                'clinic_id' => $data['clinic_id'],
                'day_of_week' => $data['day_of_week'],
                'duration_minutes' => $data['duration_minutes'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'is_active' => true,
            ]);
        }

        return ['saved' => true, 'duplicate' => null];
    }
}