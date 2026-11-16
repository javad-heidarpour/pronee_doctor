<?php

namespace App\Actions\Portal\Dashboard;

use App\Models\User;
use Illuminate\Support\Collection;

class BuildDashboardAction
{
    /**
     * داده‌ی صفحه‌ی داشبورد پزشک فعال.
     */
    public function handle(User $user, Collection $doctors, int|string $activeDoctorId): array
    {
        // دریافت پزشک فعال
        $doctor = $doctors->firstWhere('id', (int) $activeDoctorId);

        abort_unless($doctor, 403, 'دسترسی به پزشک انتخاب‌شده مجاز نیست.');

        // مطب‌های مجاز کاربر در پزشک فعال
        if ($user->role === 'secretary') {
            $assignment = $user->doctors()
                ->where('doctors.id', $doctor->id)
                ->first();

            $clinicIds = $assignment?->pivot?->clinic_ids ?? [];

            if (is_string($clinicIds)) {
                $clinicIds = json_decode($clinicIds, true) ?: [];
            }

            $clinicIds = is_array($clinicIds) ? array_map('intval', $clinicIds) : [];
        } else {
            $clinicIds = $doctor->address()->pluck('addresses.id')->map(fn ($id) => (int) $id)->all();
        }

        // آمار مربوط به مطب‌های مجاز در پزشک فعال
        $timeSlots = $doctor->timeSlots()->whereIn('clinic_id', $clinicIds);
        $stats = [
            'total_appointments' => (clone $timeSlots)
                ->where('status', 'booked')
                ->count(),

            'pending_appointments' => (clone $timeSlots)
                ->where('status', 'booked')
                ->whereHas(
                    'appointment',
                    fn ($q) => $q->where('status', 'pending')
                )
                ->count(),

            'total_reviews' => $doctor->reviews()
                ->approved()
                ->count(),

            'average_rating' => round(
                $doctor->reviews()->approved()->avg('rating') ?? 0,
                1
            ),

            'total_images' => $doctor->images()
                ->active()
                ->count(),

            'total_clinics' => count($clinicIds),
        ];

        return [
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'family' => $doctor->family,
                'caption' => $doctor->caption,
                'photo' => $doctor->photo,
                'specialty_title' => $doctor->specialty_title,
                'email' => $user->email,
            ],

            'stats' => $stats,

            'doctors' => $doctors->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'family' => $item->family,
                'photo' => $item->photo,
                'specialty_title' => $item->specialty_title,
            ])->values(),

            'activeDoctorId' => (int) $activeDoctorId,
        ];
    }
}
