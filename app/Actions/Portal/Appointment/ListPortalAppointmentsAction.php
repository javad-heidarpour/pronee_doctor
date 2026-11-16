<?php

namespace App\Actions\Portal\Appointment;

use App\Models\Appointment;
use App\Models\Doctor;

class ListPortalAppointmentsAction
{
    /**
     * @return array{appointments:\Illuminate\Contracts\Pagination\LengthAwarePaginator,stats:array<string,int>}
     */
    public function handle(Doctor $doctor, array $clinicIds, array $filters): array
    {
        $query = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('clinic_id', $clinicIds)
            ->with([
                'user',
                'timeSlot',
                'clinic.city',
                'visitReason',
                'surgeryType',
                'latestPayment',
            ])
            ->withCount('payments')
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $paymentStatus = $filters['payment_status'];
            $query->whereHas('latestPayment', function ($paymentQuery) use ($paymentStatus) {
                $paymentQuery->where('status', $paymentStatus);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('appointment_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('appointment_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($patientQuery) use ($search) {
                $patientQuery
                    ->where('patient_name', 'like', "%{$search}%")
                    ->orWhere('patient_phone', 'like', "%{$search}%")
                    ->orWhere('patient_national_code', 'like', "%{$search}%");
            });
        }

        $appointments = $query->paginate(20)->withQueryString();

        $paymentLabels = [
            'pending' => 'در انتظار پرداخت',
            'paid' => 'پرداخت شده',
            'cancelled' => 'لغو شده',
            'failed' => 'پرداخت ناموفق',
            'refunded' => 'مبلغ مسترد شده',
        ];

        $appointments->getCollection()->transform(function (Appointment $appointment) use ($paymentLabels) {
            $paymentStatus = $appointment->latestPayment?->status;
            $appointment->payment_status = $paymentStatus;
            $appointment->payment_status_label = $paymentLabels[$paymentStatus]
                ?? ($paymentStatus ? 'نامشخص' : 'عملیات پرداخت شروع نشده');

            return $appointment;
        });

        $baseQuery = fn () => Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('clinic_id', $clinicIds);

        $stats = [
            'total' => $baseQuery()->count(),
            'pending' => (clone $baseQuery())->where('status', 'pending')->count(),
            'confirmed' => (clone $baseQuery())->where('status', 'confirmed')->count(),
            'cancelled' => (clone $baseQuery())->where('status', 'cancelled')->count(),
            'visited' => (clone $baseQuery())->where('status', 'visited')->count(),
        ];

        return [
            'appointments' => $appointments,
            'stats' => $stats,
        ];
    }
}
