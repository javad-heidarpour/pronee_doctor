<?php

namespace App\Actions\Portal\Dashboard;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Collection;

class BuildDashboardAction
{
    /**
     * داده‌های عملیاتی داشبورد برای پزشک فعال و مطب‌های مجاز کاربر.
     */
    public function handle(User $user, Collection $doctors, int|string $activeDoctorId): array
    {
        $doctor = $doctors->firstWhere('id', (int) $activeDoctorId);

        abort_unless($doctor, 403, 'دسترسی به پزشک انتخاب‌شده مجاز نیست.');

        if ($user->role === 'secretary') {
            $assignment = $user->doctors()
                ->where('doctors.id', $doctor->id)
                ->first();

            $clinicIds = $assignment?->pivot?->clinic_ids ?? [];

            if (is_string($clinicIds)) {
                $clinicIds = json_decode($clinicIds, true) ?: [];
            }

            $clinicIds = is_array($clinicIds)
                ? array_values(array_unique(array_filter(array_map('intval', $clinicIds))))
                : [];
        } else {
            $clinicIds = $doctor->address()
                ->where('is_active', true)
                ->pluck('addresses.id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $today = now()->toDateString();

        $timeSlots = $doctor->timeSlots()->whereIn('clinic_id', $clinicIds);

        $todayAppointmentsQuery = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('clinic_id', $clinicIds)
            ->whereDate('appointment_date', $today);

        $todayAppointments = (clone $todayAppointmentsQuery)
            ->with(['clinic', 'latestPayment'])
            ->orderBy('appointment_time')
            ->orderBy('id')
            ->get()
            ->map(function (Appointment $appointment) {
                $paymentStatus = $appointment->latestPayment?->status;

                return [
                    'id' => $appointment->id,
                    'patient_name' => $appointment->patient_name,
                    'patient_phone' => $appointment->patient_phone,
                    'appointment_time' => $appointment->appointment_time
                        ? substr((string) $appointment->appointment_time, 0, 5)
                        : null,
                    'clinic_name' => $appointment->clinic?->name,
                    'status' => $appointment->status,
                    'status_label' => match ($appointment->status) {
                        'pending' => 'در انتظار تأیید',
                        'confirmed' => 'تأیید شده',
                        'visited' => 'ویزیت شده',
                        'cancelled' => 'لغو شده',
                        default => 'نامشخص',
                    },
                    'payment_status' => $paymentStatus,
                    'payment_status_label' => match ($paymentStatus) {
                        'paid' => 'پرداخت شده',
                        'pending', 'initiated' => 'در انتظار پرداخت',
                        'failed' => 'پرداخت ناموفق',
                        'cancelled' => 'پرداخت لغو شده',
                        'refunded' => 'مسترد شده',
                        default => 'بدون پرداخت',
                    },
                ];
            })
            ->values();

        $todayActiveAppointments = (clone $todayAppointmentsQuery)
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        $stats = [
            'total_appointments' => (clone $timeSlots)
                ->where('status', 'booked')
                ->count(),

            'pending_appointments' => (clone $timeSlots)
                ->where('status', 'booked')
                ->whereHas('appointment', fn ($query) => $query->where('status', 'pending'))
                ->count(),

            'today_total' => $todayAppointments->count(),
            'today_pending' => $todayAppointments->where('status', 'pending')->count(),
            'today_confirmed' => $todayAppointments->where('status', 'confirmed')->count(),
            'today_visited' => $todayAppointments->where('status', 'visited')->count(),
            'today_unpaid' => $todayAppointments
                ->whereIn('payment_status', ['pending', 'initiated', 'failed'])
                ->count(),

            'total_reviews' => $user->role === 'secretary'
                ? 0
                : $doctor->reviews()->approved()->count(),

            'average_rating' => $user->role === 'secretary'
                ? 0
                : round($doctor->reviews()->approved()->avg('rating') ?? 0, 1),

            'total_images' => $user->role === 'secretary'
                ? 0
                : $doctor->images()->active()->count(),

            'total_clinics' => count($clinicIds),
        ];

        $nextAppointment = $todayAppointments
            ->first(fn (array $appointment) =>
                in_array($appointment['status'], ['pending', 'confirmed'], true)
                && $appointment['appointment_time'] !== null
                && $appointment['appointment_time'] >= now()->format('H:i')
            );

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
            'todayAppointments' => $todayAppointments,
            'nextAppointment' => $nextAppointment,
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
