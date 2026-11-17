<?php

namespace App\Actions\Portal\Appointment;

use App\Models\Doctor;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ListAvailableSlotsAction
{
    /**
     * ساعت‌های آزاد آینده‌ی یک مطب.
     *
     * @return Collection|null  null اگر مطب متعلق به این پزشک یا فعال نباشد.
     */
    public function handle(Doctor $doctor, int|string $clinicId): ?Collection
    {
        $clinicExists = $doctor->address()
            ->where('addresses.id', $clinicId)
            ->where('addresses.is_active', true)
            ->exists();

        if (!$clinicExists) {
            return null;
        }

        return TimeSlot::query()
            ->where('doctor_id', $doctor->id)
            ->where('clinic_id', $clinicId)
            ->where('status', 'available')
            ->whereRaw(
                'TIMESTAMP(date, start_time) > ?',
                [now()->format('Y-m-d H:i:s')]
            )->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('appointments')
                    ->whereColumn(
                        'appointments.time_slot_id',
                        'time_slots.id'
                    )
                    ->whereIn('appointments.status', [
                        'pending',
                        'confirmed',
                    ]);
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get([
                'id',
                'clinic_id',
                'date',
                'start_time',
                'end_time',
            ]);
    }
}
