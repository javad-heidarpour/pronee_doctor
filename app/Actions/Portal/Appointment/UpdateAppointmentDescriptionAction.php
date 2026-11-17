<?php

namespace App\Actions\Portal\Appointment;

use App\DTOs\Portal\Appointment\UpdateAppointmentDescriptionData;
use App\Models\Appointment;
use App\Models\SurgeryType;
use Illuminate\Support\Facades\DB;

class UpdateAppointmentDescriptionAction
{
    public function handle(
        Appointment $appointment,
        UpdateAppointmentDescriptionData $data
    ): bool {
        return DB::transaction(function () use ($appointment, $data) {
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedAppointment->status, ['cancelled', 'canceled'], true)) {
                return false;
            }

            $surgeryTypeId = $data->surgeryTypeId;
            $newSurgeryType = trim($data->newSurgeryType ?? '');

            if ($newSurgeryType !== '') {
                $surgeryTypeId = SurgeryType::query()->firstOrCreate([
                    'title' => $newSurgeryType,
                ])->id;
            }

            $lockedAppointment->update([
                'surgery_type_id' => $surgeryTypeId,
                'surgery_date' => $data->surgeryDate,
                'description' => $data->description,
                'status' => 'visited',
            ]);

            return true;
        });
    }
}
