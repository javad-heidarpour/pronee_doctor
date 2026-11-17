<?php

namespace App\Actions\Portal\Appointment;

use App\DTOs\Portal\Appointment\CreatePortalAppointmentData;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Fee;
use App\Models\TimeSlot;
use App\Models\VisitReason;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePortalAppointmentAction
{
    public function __construct(
        private readonly EnsureSecretaryCanBookClinicAction $secretaryClinicAuthorization
    ) {
    }

    /**
     * ثبت نوبت توسط منشی، بدون درگاه پرداخت.
     *
     * @throws ValidationException
     */
    public function handle(Doctor $doctor, CreatePortalAppointmentData $data): Appointment
    {
        $newVisitReason = trim($data->newVisitReason ?? '');

        return DB::transaction(function () use ($data, $doctor, $newVisitReason) {
            // قفل پزشک، بررسی هم‌زمان نوبت فعال با کد ملی یکسان را سریالی می‌کند.
            Doctor::query()->whereKey($doctor->id)->lockForUpdate()->firstOrFail();

            $timeSlot = TimeSlot::query()
                ->whereKey($data->timeSlotId)
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
                ->where('addresses.is_active', true)
                ->lockForUpdate()
                ->exists();

            if (
                (int) $timeSlot->doctor_id !== (int) $doctor->id ||
                !$clinicExists
            ) {
                throw ValidationException::withMessages([
                    'time_slot_id' => 'این ساعت برای مطب شما معتبر نیست.',
                ]);
            }

            // بررسی دسترسی منشی به مطب انتخاب‌شده
            $user = auth('web')->user();

            if ($user instanceof User) {
                $this->secretaryClinicAuthorization->handle(
                    $user,
                    $doctor,
                    (int) $timeSlot->clinic_id
                );
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
            if ($data->patientNationalCode !== null && $data->patientNationalCode !== '') {
                $duplicate = Appointment::query()
                    ->where('doctor_id', $doctor->id)
                    ->where('patient_national_code', $data->patientNationalCode)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'patient_national_code' =>
                            'این بیمار نزد شما نوبت فعال دارد.',
                    ]);
                }
            }

            $visitReasonId = $data->visitReasonId;

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

                // نوبت ثبت‌شده توسط پزشک/منشی به حساب بیمار متصل نیست.
                'user_id' => null,
                'fee_rial' => $feeRial,
                'patient_name' => $data->patientName,
                'patient_phone' => $data->patientPhone,
                'patient_national_code' => $data->patientNationalCode,
                'description' => $data->description,

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
