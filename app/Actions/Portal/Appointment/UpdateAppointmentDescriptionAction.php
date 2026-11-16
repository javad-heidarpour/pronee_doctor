<?php

namespace App\Actions\Portal\Appointment;

use App\Models\Appointment;
use App\Models\SurgeryType;
use Illuminate\Support\Facades\DB;

class UpdateAppointmentDescriptionAction
{
    public function handle(Appointment $appointment, array $data): void
    {
        DB::transaction(function () use ($appointment, $data) {
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

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
        });
    }
}
