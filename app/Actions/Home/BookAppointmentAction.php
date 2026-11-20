<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\DTOs\Home\BookAppointmentData;
use App\Actions\Portal\Patient\ResolveOrCreatePatientAction;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorHoliday;
use App\Models\Fee;
use App\Models\TimeSlot;
use App\Models\VisitReason;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

final class BookAppointmentAction
{
    public function __construct(
        private readonly ResolveOrCreatePatientAction $resolvePatient
    ) {
    }

    public function handle(BookAppointmentData $data): Appointment
    {
        try {
            $appointment = DB::transaction(function () use ($data): Appointment {
                // Read only the identifiers first so every booking follows
                // the same lock order: doctor -> clinic -> slot.
                $slotSnapshot = TimeSlot::query()
                    ->select(['id', 'doctor_id', 'clinic_id'])
                    ->whereKey($data->timeSlotId)
                    ->first();

                if (!$slotSnapshot) {
                    throw ValidationException::withMessages([
                        'time_slot_id' => 'ساعت انتخاب شده یافت نشد.',
                    ]);
                }

                // Serialize bookings for one doctor. This also prevents two
                // concurrent requests from creating active visits for the
                // same national code at the same doctor.
                $doctor = Doctor::query()
                    ->whereKey($slotSnapshot->doctor_id)
                    ->lockForUpdate()
                    ->first();

                if (!$doctor || !$doctor->is_active) {
                    throw ValidationException::withMessages([
                        'time_slot_id' => 'پزشک انتخاب‌شده در حال حاضر فعال نیست.',
                    ]);
                }

                $clinic = $doctor->address()
                    ->whereKey($slotSnapshot->clinic_id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (!$clinic) {
                    throw ValidationException::withMessages([
                        'time_slot_id' => 'این زمان برای رزرو عمومی در دسترس نیست.',
                    ]);
                }

                $timeSlot = TimeSlot::query()
                    ->whereKey($data->timeSlotId)
                    ->lockForUpdate()
                    ->first();

                if (
                    !$timeSlot ||
                    (int) $timeSlot->doctor_id !== (int) $doctor->id ||
                    (int) $timeSlot->clinic_id !== (int) $clinic->id
                ) {
                    throw ValidationException::withMessages([
                        'time_slot_id' => 'اطلاعات ساعت انتخاب‌شده تغییر کرده است؛ دوباره انتخاب کنید.',
                    ]);
                }

                if (DoctorHoliday::query()
                    ->where('doctor_id', $doctor->id)
                    ->where('clinic_id', $clinic->id)
                    ->whereDate('holiday_date', $timeSlot->date->format('Y-m-d'))
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'time_slot_id' => 'این مطب در تاریخ انتخاب‌شده تعطیل است؛ لطفاً زمان دیگری انتخاب کنید.',
                    ]);
                }

                // Use a locking/current read so a transaction that waited for
                // the doctor lock cannot rely on an older REPEATABLE READ snapshot.
                $activeAppointment = Appointment::query()
                    ->where('time_slot_id', $timeSlot->id)
                    ->whereIn('status', ['pending', 'confirmed', 'contacted'])
                    ->lockForUpdate()
                    ->first();

                if ($activeAppointment !== null) {
                    throw ValidationException::withMessages([
                        'time_slot_id' =>
                            'متأسفانه این ساعت لحظاتی پیش رزرو شد. لطفاً ساعت دیگری انتخاب کنید.',
                    ]);
                }

                // A stale booked flag can be repaired only when there is no
                // active appointment linked to this slot.
                if ($timeSlot->status === 'booked') {
                    $timeSlot->update(['status' => 'available']);
                    $timeSlot->refresh();
                }

                if ($timeSlot->status !== 'available') {
                    throw ValidationException::withMessages([
                        'time_slot_id' => 'این ساعت در حال حاضر قابل رزرو نیست.',
                    ]);
                }

                $appointmentDateTime = Carbon::parse(
                    $timeSlot->date->format('Y-m-d') . ' ' . $timeSlot->start_time
                );

                if ($appointmentDateTime->lessThan(now()->addHour())) {
                    throw ValidationException::withMessages([
                        'time_slot_id' =>
                            'برای ثبت نوبت باید حداقل یک ساعت تا زمان مراجعه باقی مانده باشد. لطفاً زمان دیگری انتخاب کنید.',
                    ]);
                }

                $identityField = null;
                $duplicatePatientQuery = Appointment::query()
                    ->where('doctor_id', $doctor->id)
                    ->whereIn('status', ['pending', 'confirmed', 'contacted']);

                if ($data->patientNationalCode !== null && $data->patientNationalCode !== '') {
                    $identityField = 'patient_national_code';
                    $duplicatePatientQuery->where(
                        'patient_national_code',
                        $data->patientNationalCode
                    );
                } elseif ($data->patientPhone !== '') {
                    $identityField = 'patient_phone';
                    $duplicatePatientQuery
                        ->whereNull('patient_national_code')
                        ->where('patient_phone', $data->patientPhone);
                }

                if ($identityField !== null) {
                    $existingAppointment = $duplicatePatientQuery
                        ->lockForUpdate()
                        ->first();

                    if ($existingAppointment !== null) {
                        throw ValidationException::withMessages([
                            $identityField => $identityField === 'patient_national_code'
                                ? 'شما قبلاً برای این پزشک نوبت فعال دارید.'
                                : 'با این شماره همراه قبلاً برای این پزشک نوبت فعال ثبت شده است.',
                        ]);
                    }
                }

                $description = trim($data->description ?? '');
                $newVisitReason = trim($data->newVisitReason ?? '');
                $visitReasonId = null;

                if ($newVisitReason === '' && $data->visitReasonId !== null) {
                    $visitReason = VisitReason::query()->find($data->visitReasonId);

                    if (!$visitReason) {
                        throw ValidationException::withMessages([
                            'visit_reason_id' => 'دلیل مراجعه انتخاب‌شده معتبر نیست.',
                        ]);
                    }

                    $visitReasonId = $visitReason->id;
                }

                // A free-text reason belongs to this visit only; it must not
                // create an unmoderated global lookup record.
                if ($newVisitReason !== '') {
                    $description = trim($description . "\n" . 'دلیل مراجعه: ' . $newVisitReason);
                }

                $fee = Fee::query()
                    ->where('created_by', $doctor->id)
                    ->where('is_active', true)
                    ->orderByDesc('id')
                    ->first();

                $patient = $this->resolvePatient->handle(
                    $doctor,
                    $data->patientName,
                    $data->patientPhone,
                    $data->patientNationalCode
                );

                $appointment = Appointment::query()->create([
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient?->id,
                    'clinic_id' => $clinic->id,
                    'time_slot_id' => $timeSlot->id,
                    'user_id' => auth('web')->id(),
                    'fee_rial' => (int) ($fee?->fee_rial ?? 5000000),
                    'patient_name' => $data->patientName,
                    'patient_phone' => $data->patientPhone,
                    'patient_national_code' => $data->patientNationalCode,
                    'description' => $description !== '' ? $description : null,
                    'status' => 'pending',
                    'appointment_date' => $timeSlot->date,
                    'appointment_time' => $timeSlot->start_time,
                    'reservation_expires_at' => now()->addMinutes(10),
                    'visit_reason_id' => $visitReasonId,
                ]);

                $timeSlot->update(['status' => 'booked']);

                return $appointment;
            }, 3);

            // The SMS authorization is written only after the database
            // transaction commits successfully.
            session()->put(
                'sms.visit.allowed.' . $appointment->id,
                now()->addMinutes(30)->timestamp
            );

            try {
                $campaignId = (int) session('proneex_marketing_campaign_id', 0);
                $campaignDoctorId = (int) session('proneex_marketing_doctor_id', 0);
                $campaignExpiresAt = (int) session('proneex_marketing_expires_at', 0);

                if ($campaignId > 0 && $campaignDoctorId === (int) $appointment->doctor_id && $campaignExpiresAt >= now()->timestamp) {
                    $campaign = DB::table('clinic_marketing_campaigns')
                        ->where('id', $campaignId)
                        ->where('doctor_id', $appointment->doctor_id)
                        ->where(function ($query) use ($appointment) {
                            $query->whereNull('clinic_id')->orWhere('clinic_id', $appointment->clinic_id);
                        })
                        ->where('status', 'active')
                        ->where(function ($query) {
                            $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                        })
                        ->where(function ($query) {
                            $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                        })
                        ->first();

                    if ($campaign) {
                        DB::table('clinic_marketing_events')->insertOrIgnore([
                            'campaign_id' => $campaignId,
                            'appointment_id' => $appointment->id,
                            'event_type' => 'booking',
                            'occurred_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            } catch (Throwable $attributionException) {
                Log::warning('Campaign attribution could not be recorded', [
                    'appointment_id' => $appointment->id,
                    'exception' => get_class($attributionException),
                ]);
            } finally {
                session()->forget([
                    'proneex_marketing_campaign_id',
                    'proneex_marketing_doctor_id',
                    'proneex_marketing_expires_at',
                ]);
            }

            return $appointment;
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Appointment booking failed', [
                'time_slot_id' => $data->timeSlotId,
                'exception' => get_class($exception),
            ]);

            throw $exception;
        }
    }
}
