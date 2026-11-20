<?php

declare(strict_types=1);

namespace App\Actions\Visit;

use App\Actions\Sms\SendAppointmentLifecycleSmsAction;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorHoliday;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReschedulePublicAppointmentAction
{
    public function __construct(
        private readonly SendAppointmentLifecycleSmsAction $smsAction
    ) {
    }

    private const ACTIVE_STATUSES = ['pending', 'confirmed', 'contacted'];

    public function handle(int $visitNumber, string $patientPhone, int $newTimeSlotId): string
    {
        $result = DB::transaction(function () use ($visitNumber, $patientPhone, $newTimeSlotId): string {
            $appointment = Appointment::query()
                ->whereKey($visitNumber)
                ->lockForUpdate()
                ->firstOrFail();

            if (!hash_equals((string) $appointment->patient_phone, $patientPhone)) {
                throw ValidationException::withMessages([
                    'patient_phone' => 'شماره موبایل با اطلاعات ثبت‌شده برای این نوبت مطابقت ندارد.',
                ]);
            }

            if (
                $appointment->status === 'pending' &&
                $appointment->reservation_expires_at &&
                $appointment->reservation_expires_at->lessThanOrEqualTo(now())
            ) {
                throw ValidationException::withMessages([
                    'new_time_slot_id' => 'مهلت رزرو اولیه به پایان رسیده است؛ لطفاً نوبت جدیدی ثبت کنید.',
                ]);
            }

            if (!in_array($appointment->status, self::ACTIVE_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'new_time_slot_id' => 'وضعیت فعلی نوبت اجازه جابه‌جایی آنلاین را نمی‌دهد.',
                ]);
            }

            $currentDateTime = Carbon::parse(
                $appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->appointment_time
            );

            if ($currentDateTime->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'new_time_slot_id' => 'زمان مراجعه فعلی گذشته است و امکان جابه‌جایی آنلاین وجود ندارد.',
                ]);
            }

            if ((int) $appointment->time_slot_id === $newTimeSlotId) {
                throw ValidationException::withMessages([
                    'new_time_slot_id' => 'این ساعت، زمان فعلی نوبت شماست.',
                ]);
            }

            $doctor = Doctor::query()
                ->whereKey($appointment->doctor_id)
                ->lockForUpdate()
                ->first();

            if (!$doctor || !$doctor->is_active) {
                throw ValidationException::withMessages([
                    'new_time_slot_id' => 'پزشک این نوبت در حال حاضر فعال نیست.',
                ]);
            }

            $clinic = $doctor->address()
                ->whereKey($appointment->clinic_id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$clinic) {
                throw ValidationException::withMessages([
                    'new_time_slot_id' => 'شعبه فعلی برای جابه‌جایی نوبت فعال نیست.',
                ]);
            }

            $slotIds = collect([$appointment->time_slot_id, $newTimeSlotId])
                ->filter()
                ->map(static fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();

            $slots = TimeSlot::query()
                ->whereIn('id', $slotIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $newSlot = $slots->get($newTimeSlotId);

            if (
                !$newSlot ||
                (int) $newSlot->doctor_id !== (int) $appointment->doctor_id ||
                (int) $newSlot->clinic_id !== (int) $appointment->clinic_id ||
                $newSlot->status !== 'available'
            ) {
                throw ValidationException::withMessages([
                    'new_time_slot_id' => 'ساعت انتخاب‌شده دیگر آزاد نیست یا متعلق به شعبه فعلی شما نیست.',
                ]);
            }

            if (DoctorHoliday::query()
                ->where('doctor_id', $appointment->doctor_id)
                ->where('clinic_id', $appointment->clinic_id)
                ->whereDate('holiday_date', $newSlot->date->format('Y-m-d'))
                ->exists()) {
                throw ValidationException::withMessages([
                    'new_time_slot_id' => 'این مطب در تاریخ انتخاب‌شده تعطیل است؛ لطفاً زمان دیگری انتخاب کنید.',
                ]);
            }

            $newDateTime = Carbon::parse(
                $newSlot->date->format('Y-m-d') . ' ' . $newSlot->start_time
            );

            if ($newDateTime->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'new_time_slot_id' => 'امکان انتخاب ساعت گذشته وجود ندارد.',
                ]);
            }

            $slotAlreadyHasActiveAppointment = Appointment::query()
                ->where('time_slot_id', $newSlot->id)
                ->whereIn('status', self::ACTIVE_STATUSES)
                ->exists();

            if ($slotAlreadyHasActiveAppointment) {
                throw ValidationException::withMessages([
                    'new_time_slot_id' => 'این ساعت قبلاً توسط بیمار دیگری رزرو شده است. لطفاً زمان دیگری انتخاب کنید.',
                ]);
            }

            $oldSlot = $appointment->time_slot_id
                ? $slots->get((int) $appointment->time_slot_id)
                : null;

            $appointment->update([
                'time_slot_id' => $newSlot->id,
                'appointment_date' => $newSlot->date,
                'appointment_time' => $newSlot->start_time,
            ]);

            $newSlot->update(['status' => 'booked']);

            if ($oldSlot) {
                $oldSlotHasAnotherActiveAppointment = Appointment::query()
                    ->where('time_slot_id', $oldSlot->id)
                    ->where('id', '!=', $appointment->id)
                    ->whereIn('status', self::ACTIVE_STATUSES)
                    ->exists();

                if (!$oldSlotHasAnotherActiveAppointment) {
                    $oldSlot->update(['status' => 'available']);
                }
            }

            return 'rescheduled';
        });

        if ($result === 'rescheduled') {
            $this->smsAction->handle($visitNumber, 'updated');
        }

        return $result;
    }
}
