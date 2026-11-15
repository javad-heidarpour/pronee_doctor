<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\AppointmentPayment;

class VisitController extends Controller
{

    public function show(int $visit_number): Response
    {
        $appointment = Appointment::query()
            ->with([
                'doctor.user',
                'clinic',
                'latestPayment',
            ])
            ->find($visit_number);

        if (!$appointment) {
            return Inertia::render('Visit/Show', [
                'visit' => null,
            ]);
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

        $visit = [
            'number' => $appointment->id,

            'patient_name' => $appointment->patient_name,

            'doctor_name' => $doctorName !== ''
                ? 'دکتر ' . $doctorName
                : 'پزشک',

            'clinic_name' => $clinic?->name
                ?? 'مطب پزشک',

            'clinic_address' => $clinic?->full_address
                ?? 'آدرس مطب پزشک',

            'clinic_phone' => $clinic?->phone
                ?? 'تلفن مطب پزشک',

            'date' => $appointment->appointment_date,

            'time' => $appointment->appointment_time
                ? substr($appointment->appointment_time, 0, 5)
                : null,

            'appointment_status' => $appointment->status,

            'fee_rial' => $appointment->fee_rial,

            'payment_status' => $paymentStatus,
        ];

        return Inertia::render('Visit/Show', [
            'visit' => $visit,
        ]);
    }

}