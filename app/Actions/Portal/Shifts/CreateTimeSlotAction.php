<?php

namespace App\Actions\Portal\Shifts;

use App\Actions\Portal\Clinic\EnsureDoctorOwnsActiveClinicAction;
use App\DTOs\Portal\Scheduling\TimeSlotData;
use App\Models\Doctor;
use App\Models\DoctorHoliday;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;

class CreateTimeSlotAction
{
    public function __construct(
        private readonly EnsureDoctorOwnsActiveClinicAction $ensureClinic
    ) {
    }

    public function handle(Doctor $doctor, TimeSlotData $data): bool
    {
        return DB::transaction(function () use ($doctor, $data) {
            Doctor::query()->whereKey($doctor->id)->lockForUpdate()->firstOrFail();
            $this->ensureClinic->handle($doctor, $data->clinicId);

            if (DoctorHoliday::query()
                ->where('doctor_id', $doctor->id)
                ->where('clinic_id', $data->clinicId)
                ->whereDate('holiday_date', $data->date)
                ->exists()) {
                return false;
            }

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
