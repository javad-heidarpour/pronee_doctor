<?php

namespace App\\Actions\\Portal\\Appointment;

use App\\Models\\Doctor;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Collection;

class ListAvailableDatesAction
{
    /**
     * تاریخ‌هایی که در مطب مشخص، ساعت آزاد آینده دارند.
     *
     * دسترسی پزشک/منشی به مطب پیش از فراخوانی این Action در Controller
     * بررسی می‌شود؛ این Action نیز داده‌ها را به پزشک و مطب محدود می‌کند.
     */
    public function handle(Doctor $doctor, int $clinicId): Collection
    {
        return $doctor->timeSlots()
            ->where('clinic_id', $clinicId)
            ->where('status', 'available')
            ->whereDate('date', '>=', now()->toDateString())
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('appointments')
                    ->whereColumn('appointments.time_slot_id', 'time_slots.id')
                    ->whereIn('appointments.status', ['pending', 'confirmed']);
            })
            ->distinct()
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($date) => (string) $date)
            ->values();
    }
}
