<?php

namespace App\Actions\Visit;

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use Illuminate\Support\Facades\URL;

class ShowVisitAction
{
    /**
     * اطلاعات نمایشی ویزیت؛ در صورت نبودن نوبت null برمی‌گرداند.
     */
    public function handle(int $visitNumber): ?array
    {
        $appointment = Appointment::query()
            ->with([
                'doctor.user',
                'clinic',
                'latestPayment',
            ])
            ->find($visitNumber);

        if (!$appointment) {
            return null;
        }

        $doctor = $appointment->doctor;
        $clinic = $appointment->clinic;

        // آخرین تلاش پرداخت
        $payment = $appointment->latestPayment;

        // آیا برای این نوبت قبلاً پرداخت موفق ثبت شده است؟
        $hasPaidPayment = AppointmentPayment::query()
            ->where('appointment_id', $appointment->id)
            ->where('status', 'paid')
            ->exists();

        $doctorName = trim(
            ($doctor?->name ?? '') . ' ' .
            ($doctor?->family ?? '')
        );

        $paymentStatus = $hasPaidPayment
            ? 'paid'
            : ($payment?->status ?? 'unpaid');

        return [
            'number' => $appointment->id,
            'payment_url' => URL::temporarySignedRoute(
                'payment.pay',
                now()->addMinutes(30),
                ['visit_number' => $appointment->id]
            ),
            'patient_name' => $appointment->patient_name,
            'doctor_name' => $doctorName !== '' ? 'دکتر ' . $doctorName : 'پزشک',
            'clinic_name' => $clinic?->name ?? 'مطب پزشک',
            'clinic_address' => $clinic?->full_address ?? 'آدرس مطب پزشک',
            'clinic_phone' => $clinic?->phone ?? 'تلفن مطب پزشک',
            'date' => $appointment->appointment_date,
            'time' => $appointment->appointment_time
                ? substr($appointment->appointment_time, 0, 5)
                : null,
            'appointment_status' => $appointment->status,
            'appointment_status_label' => $appointment->status_badge,
            'fee_rial' => $appointment->fee_rial,
            'payment_status' => $paymentStatus,
        ];
    }
}
