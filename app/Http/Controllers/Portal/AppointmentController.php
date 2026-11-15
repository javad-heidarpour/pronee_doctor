<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Models\VisitReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Models\Fee;

class AppointmentController extends Controller
{
    /**
     * دریافت تمام ساعت‌های آزاد آینده یک مطب
     */
    public function availableSlots(Request $request)
    {
        $validated = $request->validate([
            'clinic_id' => ['required', 'integer'],
        ]);

        $user = Auth::guard('web')->user();
        $doctor = $user->doctorProfile;

        abort_unless($doctor, 401);

        $clinicExists = $doctor->address()
            ->where('addresses.id', $validated['clinic_id'])
            ->exists();

        if (!$clinicExists) {
            return response()->json([
                'message' => 'مطب انتخاب‌شده معتبر نیست.',
            ], 422);
        }

        $slots = TimeSlot::query()
            ->where('doctor_id', $doctor->id)
            ->where('clinic_id', $validated['clinic_id'])
            ->where('status', 'available')
            ->whereRaw(
                "TIMESTAMP(date, start_time) > ?",
                [now()->format('Y-m-d H:i:s')]
            )->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('appointments')
                    ->whereColumn(
                        'appointments.time_slot_id',
                        'time_slots.id'
                    )
                    ->whereIn('appointments.status', [
                        'pending',
                        'confirmed',
                    ]);
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get([
                'id',
                'clinic_id',
                'date',
                'start_time',
                'end_time',
            ]);

        return response()->json([
            'success' => true,
            'slots' => $slots,
        ]);
    }

    /**
     * ثبت نوبت توسط منشی، بدون درگاه پرداخت
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
            'patient_name' => ['required', 'string', 'min:3', 'max:255'],
            'patient_phone' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
            ],
            'patient_national_code' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[0-9]{10}$/',
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'visit_reason_id' => [
                'nullable',
                'integer',
                'exists:visit_reasons,id',
            ],
            'new_visit_reason' => ['nullable', 'string', 'max:150'],
        ]);

        $user = Auth::guard('web')->user();
        $doctor = $user->doctorProfile;

        abort_unless($doctor, 401);

        $newVisitReason = trim($validated['new_visit_reason'] ?? '');

        try {
            $appointment = DB::transaction(function () use ($validated, $doctor, $newVisitReason) {
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

                // ساعت باید آزاد باشد و نوبت فعال دیگری نداشته باشد
                $alreadyBooked = Appointment::query()
                    ->where('time_slot_id', $timeSlot->id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->exists();

                if (
                    $alreadyBooked ||
                    $timeSlot->status !== 'available'
                ) {
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
                    $visitReason = VisitReason::firstOrCreate([
                        'title' => $newVisitReason,
                    ]);

                    $visitReasonId = $visitReason->id;
                }
                $fee = Fee::where('is_active', true)
                    ->orderByDesc('id')
                    ->first();

                $feeRial = (int) ($fee?->fee_rial ?? 5000000);
                $user_id = null;
                if (Auth::guard('web')->id()) {
                    $user_id = Auth::guard('web')->id();
                }
                $appointment = Appointment::create([
                    'doctor_id' => $doctor->id,
                    'clinic_id' => $timeSlot->clinic_id,
                    'time_slot_id' => $timeSlot->id,

                    // نوبت منشی لزوماً حساب کاربری بیمار ندارد
                    'user_id' => $user_id,
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

            return response()->json([
                'success' => true,
                'message' => 'نوبت با موفقیت ثبت شد.',
                'appointment' => [
                    'id' => $appointment->id,
                    'patient_name' => $appointment->patient_name,
                    'appointment_date' => $appointment->appointment_date,
                    'appointment_time' => $appointment->appointment_time,
                    'status' => $appointment->status,
                ],
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        }
    }
}
