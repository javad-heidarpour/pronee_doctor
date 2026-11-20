<?php

namespace App\Actions\Visit;

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Models\TimeSlot;
use Carbon\Carbon;
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

        // آخرین پرداخت موفق مبنای رسید است؛ تلاش‌های بعدی ناموفق نباید رسید قبلی را پنهان کنند.
        $paidPayment = AppointmentPayment::query()
            ->where('appointment_id', $appointment->id)
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();

        $hasPaidPayment = $paidPayment !== null;

        $paymentReceipt = $paidPayment ? [
            'receipt_number' => 'PRX-' . str_pad((string) $paidPayment->id, 8, '0', STR_PAD_LEFT),
            'amount_rial' => (int) $paidPayment->amount_rial,
            'amount_toman' => intdiv((int) $paidPayment->amount_rial, 10),
            'reference' => $paidPayment->ref_num,
            'gateway' => $paidPayment->gateway_name === 'sep' ? 'سامان‌کیش' : 'درگاه بانکی',
            'paid_at' => $paidPayment->paid_at?->format('Y-m-d H:i:s'),
        ] : null;

        $doctorName = trim(
            ($doctor?->name ?? '') . ' ' .
            ($doctor?->family ?? '')
        );

        $paymentStatus = $hasPaidPayment
            ? 'paid'
            : ($payment?->status ?? 'unpaid');

        $canReschedule = in_array($appointment->status, ['pending', 'confirmed', 'contacted'], true)
            && ($appointment->status !== 'pending' || !$appointment->reservation_expires_at || $appointment->reservation_expires_at->isFuture())
            && (bool) $doctor?->is_active
            && (bool) $clinic?->is_active
            && Carbon::parse($appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->appointment_time)->isFuture();

        $minimumRescheduleAt = now()->addHour();
        $minimumRescheduleDate = $minimumRescheduleAt->toDateString();
        $minimumRescheduleTime = $minimumRescheduleAt->format('H:i:s');

        $availableSlots = $canReschedule
            ? TimeSlot::query()
                ->where('doctor_id', $appointment->doctor_id)
                ->where('clinic_id', $appointment->clinic_id)
                ->where('status', 'available')
                ->whereDoesntHave('appointment', fn ($query) => $query->whereIn('status', ['pending', 'confirmed', 'contacted']))
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('doctor_holidays')
                        ->whereColumn('doctor_holidays.doctor_id', 'time_slots.doctor_id')
                        ->whereColumn('doctor_holidays.clinic_id', 'time_slots.clinic_id')
                        ->whereColumn('doctor_holidays.holiday_date', 'time_slots.date');
                })
                ->where(function ($query) use ($minimumRescheduleDate, $minimumRescheduleTime): void {
                    $query->whereDate('date', '>', $minimumRescheduleDate)
                        ->orWhere(function ($sameDay) use ($minimumRescheduleDate, $minimumRescheduleTime): void {
                            $sameDay->whereDate('date', $minimumRescheduleDate)
                                ->where('start_time', '>=', $minimumRescheduleTime);
                        });
                })
                ->orderBy('date')
                ->orderBy('start_time')
                ->limit(40)
                ->get(['id', 'date', 'start_time'])
                ->map(fn (TimeSlot $slot): array => [
                    'id' => $slot->id,
                    'date' => $slot->date->format('Y-m-d'),
                    'start_time' => substr((string) $slot->start_time, 0, 5),
                ])
                ->values()
            : collect();

        return [
            'number' => $appointment->id,
            'payment_url' => URL::temporarySignedRoute(
                'payment.pay',
                now()->addMinutes(30),
                ['visit_number' => $appointment->id]
            ),
            'cancel_url' => URL::temporarySignedRoute(
                'visit.cancel',
                now()->addDays(180),
                ['visit_number' => $appointment->id]
            ),
            'reschedule_url' => URL::temporarySignedRoute(
                'visit.reschedule',
                now()->addDays(180),
                ['visit_number' => $appointment->id]
            ),
            'can_reschedule' => $canReschedule,
            'available_slots' => $availableSlots,
            'can_cancel' => in_array($appointment->status, ['pending', 'confirmed', 'contacted'], true)
                && !$hasPaidPayment
                && Carbon::parse($appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->appointment_time)->isFuture(),
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
            'payment_receipt' => $paymentReceipt,
        ];
    }
}
