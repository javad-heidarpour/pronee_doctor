<?php

declare(strict_types=1);

namespace App\Actions\Visit;

use App\Actions\Sms\SendAppointmentLifecycleSmsAction;
use App\Models\Appointment;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelPublicAppointmentAction
{
    public function __construct(
        private readonly SendAppointmentLifecycleSmsAction $smsAction
    ) {
    }

    public function handle(int $visitNumber, string $patientPhone): string
    {
        $result = DB::transaction(function () use ($visitNumber, $patientPhone): string {
            $appointment = Appointment::query()
                ->whereKey($visitNumber)
                ->lockForUpdate()
                ->firstOrFail();

            if (!hash_equals((string) $appointment->patient_phone, $patientPhone)) {
                throw ValidationException::withMessages([
                    'patient_phone' => 'شماره موبایل با اطلاعات ثبت‌شده برای این نوبت مطابقت ندارد.',
                ]);
            }

            if (in_array($appointment->status, ['cancelled', 'canceled'], true)) {
                return 'already_cancelled';
            }

            if (in_array($appointment->status, ['visited', 'completed'], true)) {
                throw ValidationException::withMessages([
                    'patient_phone' => 'نوبت انجام‌شده قابل لغو نیست.',
                ]);
            }

            if (!in_array($appointment->status, ['pending', 'confirmed', 'contacted'], true)) {
                throw ValidationException::withMessages([
                    'patient_phone' => 'وضعیت فعلی نوبت اجازه لغو آنلاین را نمی‌دهد.',
                ]);
            }

            $appointmentDateTime = Carbon::parse(
                $appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->appointment_time
            );

            if ($appointmentDateTime->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'patient_phone' => 'زمان مراجعه گذشته است و امکان لغو آنلاین وجود ندارد.',
                ]);
            }

            if ($appointment->payments()->where('status', 'paid')->exists()) {
                throw ValidationException::withMessages([
                    'patient_phone' => 'این نوبت پرداخت موفق دارد. برای لغو و پیگیری بازپرداخت با مطب تماس بگیرید.',
                ]);
            }

            $slot = $appointment->time_slot_id
                ? TimeSlot::query()->whereKey($appointment->time_slot_id)->lockForUpdate()->first()
                : null;

            $appointment->update(['status' => 'cancelled']);

            if ($slot) {
                $anotherActiveAppointmentExists = Appointment::query()
                    ->where('time_slot_id', $slot->id)
                    ->where('id', '!=', $appointment->id)
                    ->whereIn('status', ['pending', 'confirmed', 'contacted'])
                    ->exists();

                if (!$anotherActiveAppointmentExists) {
                    $slot->update(['status' => 'available']);
                }
            }

            return 'cancelled';
        });

        if ($result === 'cancelled') {
            $this->smsAction->handle($visitNumber, 'cancelled');
        }

        return $result;
    }
}
