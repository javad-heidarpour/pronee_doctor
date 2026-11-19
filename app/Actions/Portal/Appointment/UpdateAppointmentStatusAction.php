<?php

namespace App\Actions\Portal\Appointment;

use App\Models\Appointment;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAppointmentStatusAction
{
    /**
     * Update appointment and its slot atomically.
     *
     * @return string updated|already_visited|visit_completion_not_allowed
     */
    public function handle(Appointment $appointment, string $newStatus): string
    {
        if (!in_array($newStatus, ['pending', 'confirmed', 'cancelled', 'visited'], true)) {
            throw ValidationException::withMessages([
                'status' => 'وضعیت انتخاب‌شده معتبر نیست.',
            ]);
        }

        return DB::transaction(function () use ($appointment, $newStatus) {
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAppointment->status === 'visited') {
                return 'already_visited';
            }

            // تکمیل ویزیت فقط از مسیر ثبت جزئیات ویزیت انجام می‌شود.
            if ($newStatus === 'visited') {
                return 'visit_completion_not_allowed';
            }

            // پرداخت موفق را نمی‌توان با تغییر دستی وضعیت نوبت لغو یا بازنویسی کرد.
            // این کار باید از فرایند مستقل استرداد وجه انجام شود.
            $hasPaidPayment = $lockedAppointment->payments()
                ->where('status', 'paid')
                ->exists();

            if ($hasPaidPayment) {
                throw ValidationException::withMessages([
                    'status' => 'این نوبت پرداخت موفق دارد؛ تغییر وضعیت آن از این بخش مجاز نیست.',
                ]);
            }

            $timeSlot = $lockedAppointment->time_slot_id
                ? TimeSlot::query()
                    ->whereKey($lockedAppointment->time_slot_id)
                    ->lockForUpdate()
                    ->first()
                : null;

            if (in_array($newStatus, ['pending', 'confirmed'], true)) {
                // A cancelled legacy appointment without a slot cannot be safely
                // reactivated: it would become active without reserving any time.
                if (!$lockedAppointment->time_slot_id || !$timeSlot) {
                    throw ValidationException::withMessages([
                        'status' => 'برای فعال‌سازی مجدد این نوبت، ساعت معتبر در دسترس نیست. لطفاً نوبت جدیدی با انتخاب ساعت آزاد ثبت کنید.',
                    ]);
                }

                $anotherAppointmentExists = Appointment::query()
                    ->where('time_slot_id', $lockedAppointment->time_slot_id)
                    ->where('id', '!=', $lockedAppointment->id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->exists();

                if ($anotherAppointmentExists) {
                    throw ValidationException::withMessages([
                        'status' => 'امکان فعال‌سازی مجدد این نوبت وجود ندارد؛ زیرا ساعت انتخاب‌شده توسط نوبت دیگری رزرو شده است. لطفاً ساعت دیگری انتخاب کنید.',
                    ]);
                }
            }

            $lockedAppointment->update(['status' => $newStatus]);

            if ($timeSlot) {
                if ($newStatus === 'cancelled') {
                    $hasActiveAppointment = Appointment::query()
                        ->where('time_slot_id', $timeSlot->id)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->exists();

                    if (!$hasActiveAppointment) {
                        $timeSlot->update(['status' => 'available']);
                    }
                } elseif (in_array($newStatus, ['pending', 'confirmed'], true)) {
                    $timeSlot->update(['status' => 'booked']);
                }
            }

            return 'updated';
        });
    }
}
