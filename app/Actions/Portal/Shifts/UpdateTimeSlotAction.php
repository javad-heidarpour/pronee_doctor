<?php

namespace App\Actions\Portal\Shifts;

use App\Actions\Portal\Clinic\EnsureDoctorOwnsActiveClinicAction;
use App\DTOs\Portal\Scheduling\TimeSlotData;
use App\Models\Doctor;
use App\Models\DoctorHoliday;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;

class UpdateTimeSlotAction
{
    public function __construct(
        private readonly EnsureDoctorOwnsActiveClinicAction $ensureClinic
    ) {
    }

    public function handle(Doctor $doctor, TimeSlot $slot, TimeSlotData $data): bool
    {
        return DB::transaction(function () use ($doctor, $slot, $data) {
            Doctor::query()->whereKey($doctor->id)->lockForUpdate()->firstOrFail();
            $this->ensureClinic->handle($doctor, $data->clinicId);

            $lockedSlot = TimeSlot::query()
                ->whereKey($slot->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedSlot || (int) $lockedSlot->doctor_id !== (int) $doctor->id || $lockedSlot->isBooked()) {
                return false;
            }

            if (DoctorHoliday::query()
                ->where('doctor_id', $doctor->id)
                ->where('clinic_id', $data->clinicId)
                ->whereDate('holiday_date', $data->date)
                ->exists()) {
                return false;
            }

            $overlap = TimeSlot::query()
                ->overlapping(
                    (int) $doctor->id,
                    $data->date,
                    $data->startTime,
                    $data->endTime,
                    (int) $lockedSlot->id
                )
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
                return false;
            }

            $lockedSlot->update([
                'clinic_id' => $data->clinicId,
                'date' => $data->date,
                'start_time' => $data->startTime,
                'end_time' => $data->endTime,
            ]);

            return true;
        });
    }
}
