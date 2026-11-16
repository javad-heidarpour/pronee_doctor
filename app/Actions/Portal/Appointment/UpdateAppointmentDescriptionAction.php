<?php

namespace App\Actions\Portal\Appointment;

use App\Models\Appointment;
use App\Models\SurgeryType;
use Illuminate\Support\Facades\DB;

class UpdateAppointmentDescriptionAction
{
    public function handle(Appointment $appointment, array $data): bool
    {
        return DB::transaction(function () use ($appointment, $data) {
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedAppointment->status, ['cancelled', 'canceled'], true)) {
                return false;
            }

            $newSurgeryType = trim((string) ($data['new_surgery_type'] ?? ''));

            if ($newSurgeryType !== '') {
                $surgeryType = SurgeryType::query()->firstOrCreate([
                    'title' => $newSurgeryType,
                ]);

                $data['surgery_type_id'] = $surgeryType->id;
            }

            $lockedAppointment->update([
                'surgery_type_id' => $data['surgery_type_id'] ?? null,
                'surgery_date' => $data['surgery_date'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => 'visited',
            ]);

            return true;
        });
    }
}
