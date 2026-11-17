<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\DTOs\Home\BookAppointmentData;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Fee;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

final class BookAppointmentAction
{
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

                $isAlreadyBooked = Appointment::query()
                    ->where('time_slot_id', $timeSlot->id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->exists();

                if ($isAlreadyBooked) {
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

                if ($appointmentDateTime->lessThanOrEqualTo(now())) {
                    throw ValidationException::withMessages([
                        'time_slot_id' =>
                            'زمان این نوبت گذشته است. لطفاً ساعت دیگری انتخاب کنید.',
                    ]);
                }

                if ($data->patientNationalCode !== null && $data->patientNationalCode !== '') {
                    $existingAppointment = Appointment::query()
                        ->where('patient_national_code', $data->patientNationalCode)
                        ->where('doctor_id', $doctor->id)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->exists();

                    if ($existingAppointment) {
                        throw ValidationException::withMessages([
                            'patient_national_code' =>
                                'شما قبلاً برای این پزشک نوبت فعال دارید.',
                        ]);
                    }
                }

                $description = trim($data->description ?? '');
                $newVisitReason = trim($data->newVisitReason ?? '');

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

                return Appointment::query()->create([
                    'doctor_id' => $doctor->id,
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
                    'visit_reason_id' => $data->visitReasonId,
                ]);
            });

            // The SMS authorization is written only after the database
            // transaction commits successfully.
            session()->put(
                'sms.visit.allowed.' . $appointment->id,
                now()->addMinutes(30)->timestamp
            );

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
