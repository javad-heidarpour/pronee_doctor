<?php

namespace App\Actions\Portal\Appointment;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Fee;
use App\Models\TimeSlot;
use App\Models\VisitReason;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePortalAppointmentAction
{
    /**
     * ثبت نوبت توسط منشی، بدون درگاه پرداخت.
     *
     * @throws ValidationException
     */
    public function handle(Doctor $doctor, array $validated): Appointment
    {
        $newVisitReason = trim($validated['new_visit_reason'] ?? '');

        return DB::transaction(function () use ($validated, $doctor, $newVisitReason) {
            $timeSlot = TimeSlot::query()
                ->whereKey($validated['time_slot_id'])
                ->lockForUpdate()
                ->first();

            if (!$timeSlot) {
                throw ValidationException::withMessages([
                    'time_slot_id' => 'ساعت انتخاب‌شده پیدا نشد.',
                ]);
            }

            // بررسی تعلق ساعت به پزشک و مطب
            $clinicExists = $doctor->address()
                ->where('addresses.id', $timeSlot->clinic_id)
                ->exists();

            if (
                (int) $timeSlot->doctor_id !== (int) $doctor->id ||
                !$clinicExists
            ) {
                throw ValidationException::withMessages([
                    'time_slot_id' => 'این ساعت برای مطب شما معتبر نیست.',
                ]);
            }

            // منشی فقط می‌تواند در مطب‌هایی نوبت ثبت کند که صریحاً
            // در ارتباط همان پزشک با حساب منشی تخصیص داده شده‌اند.
            $user = Auth::guard('web')->user();

            if ($user && $user->role === 'secretary') {
                $assignment = $user->doctors()
                    ->where('doctors.id', $doctor->id)
                    ->first();

                $allowedClinicIds = $assignment?->pivot?->clinic_ids ?? [];

                if (is_string($allowedClinicIds)) {
                    $allowedClinicIds = json_decode($allowedClinicIds, true) ?: [];
                }

                $allowedClinicIds = is_array($allowedClinicIds)
                    ? array_map('intval', $allowedClinicIds)
                    : [];

                if (!in_array((int) $timeSlot->clinic_id, $allowedClinicIds, true)) {
                    throw ValidationException::withMessages([
                        'time_slot_id' => 'شما اجازه ثبت نوبت در این مطب را ندارید.',
                    ]);
                }
            }

            // ساعت باید آزاد باشد و نوبت فعال دیگری نداشته باشد
            $alreadyBooked = Appointment::query()
                ->where('time_slot_id', $timeSlot->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if ($alreadyBooked || $timeSlot->status !== 'available') {
                throw ValidationException::withMessages([
                    'time_slot_id' =>
                        'این ساعت قبلاً رزرو شده است؛ لطفاً ساعت دیگری انتخاب کنید.',
                ]);
            }

            $appointmentDateTime = Carbon::parse(
                $timeSlot->date->format('Y-m-d') . ' ' .
                $timeSlot->start_time
            );

            if ($appointmentDateTime->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'time_slot_id' =>
                        'امکان ثبت نوبت برای زمان گذشته وجود ندارد.',
                ]);
            }

            // جلوگیری از داشتن دو نوبت فعال برای یک کد ملی نزد همین پزشک
            if (!empty($validated['patient_national_code'])) {
                $duplicate = Appointment::query()
                    ->where('doctor_id', $doctor->id)
                    ->where(
                        'patient_national_code',
                        $validated['patient_national_code']
                    )
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'patient_national_code' =>
                            'این بیمار نزد شما نوبت فعال دارد.',
                    ]);
                }
            }

            $visitReasonId = $validated['visit_reason_id'] ?? null;

            if ($newVisitReason !== '') {
                $visitReasonId = VisitReason::firstOrCreate([
                    'title' => $newVisitReason,
                ])->id;
            }

            $fee = Fee::where('created_by', $timeSlot->doctor_id)
                    ->where('is_active', true)
                ->orderByDesc('id')
                ->first();

            $feeRial = (int) ($fee?->fee_rial ?? 5000000);

            $appointment = Appointment::create([
                'doctor_id' => $doctor->id,
                'clinic_id' => $timeSlot->clinic_id,
                'time_slot_id' => $timeSlot->id,

                // نوبت منشی لزوماً حساب کاربری بیمار ندارد
                'user_id' => Auth::guard('web')->id(),
                'fee_rial' => $feeRial,
                'patient_name' => $validated['patient_name'],
                'patient_phone' => $validated['patient_phone'],
                'patient_national_code' =>
                    $validated['patient_national_code'] ?? null,
                'description' => $validated['description'] ?? null,

                'status' => 'pending',
                'appointment_date' => $timeSlot->date,
                'appointment_time' => $timeSlot->start_time,

                // این نوبت وابسته به پرداخت آنلاین نیست
                'reservation_expires_at' => null,

                'visit_reason_id' => $visitReasonId,
            ]);

            $timeSlot->update([
                'status' => 'booked',
            ]);

            return $appointment;
        });
    }
}
