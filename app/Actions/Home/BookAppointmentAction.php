<?php

namespace App\Actions\Home;

use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Models\VisitReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Fee;

class BookAppointmentAction
{
    public function handle(Request $request)
    {
        $validated = $request->validate([
            'time_slot_id' => 'required|integer|exists:time_slots,id',
            'patient_name' => 'required|string|min:3|max:255',
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
            'description' => 'nullable|string|max:1000',
            'visit_reason_id' => [
                'nullable',
                'integer',
                'exists:visit_reasons,id',
            ],

            'new_visit_reason' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);
        try {

            return DB::transaction(function () use ($validated) {

                $timeSlot = TimeSlot::where('id', $validated['time_slot_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$timeSlot) {
                    return back()->withErrors([
                        'time_slot_id' => 'ساعت انتخاب شده یافت نشد.',
                    ]);
                }

                $isAlreadyBooked = Appointment::where(
                    'time_slot_id',
                    $timeSlot->id
                )
                    ->whereIn('status', [
                        'pending',
                        'confirmed',
                    ])
                    ->exists();

                if ($isAlreadyBooked) {
                    return back()->withErrors([
                        'time_slot_id' =>
                            'متأسفانه این ساعت لحظاتی پیش رزرو شد. لطفاً ساعت دیگری انتخاب کنید.',
                    ]);
                }

                // آزادسازی زمان در صورت نبود نوبت فعال
                if ($timeSlot->status === 'booked') {
                    $timeSlot->update([
                        'status' => 'available',
                    ]);

                    $timeSlot->refresh();
                }
             
                // if ($timeSlot->date->isPast()) {

                //     return back()->withErrors([
                //         'time_slot_id' =>
                //             'امکان رزرو نوبت برای تاریخ‌های گذشته وجود ندارد.',
                //     ]);
                // }

                $appointmentDateTime = Carbon::parse(
                    $timeSlot->date->format('Y-m-d') . ' ' . $timeSlot->start_time
                );

                if ($appointmentDateTime->lessThanOrEqualTo(now())) {
                    return back()->withErrors([
                        'time_slot_id' =>
                            'زمان این نوبت گذشته است. لطفاً ساعت دیگری انتخاب کنید.',
                    ]);
                }

                if (!empty($validated['patient_national_code'])) {

                    $existingAppointment = Appointment::where(
                        'patient_national_code',
                        $validated['patient_national_code']
                    )
                        ->where('doctor_id', $timeSlot->doctor_id)
                        ->whereIn('status', [
                            'pending',
                            'confirmed',
                        ])
                        ->exists();

                    if ($existingAppointment) {

                        return back()->withErrors([
                            'patient_national_code' =>
                                'شما قبلاً برای این پزشک نوبت فعال دارید.',
                        ]);
                    }
                }

                $visitReasonId = $validated['visit_reason_id'] ?? null;

                $newVisitReason = trim(
                    $validated['new_visit_reason'] ?? ''
                );

                if ($newVisitReason !== '') {
                    $visitReason = VisitReason::firstOrCreate([
                        'title' => $newVisitReason,
                    ]);

                    $visitReasonId = $visitReason->id;
                }
                $fee = Fee::where('created_by', $timeSlot->doctor_id)
                    ->where('is_active', true)
                    ->orderByDesc('id')
                    ->first();

                $feeRial = (int) ($fee?->fee_rial ?? 5000000);
                // اجازه ارسال پیامک فقط در همان نشست رزروکننده و به‌مدت ۳۰ دقیقه معتبر است.
                $appointment = Appointment::create([

                    'doctor_id' => $timeSlot->doctor_id,
                    'clinic_id' => $timeSlot->clinic_id,
                    'time_slot_id' => $timeSlot->id,
                    'user_id' => auth()->id(),
                    'fee_rial' => $feeRial,
                    'patient_name' => $validated['patient_name'],
                    'patient_phone' => $validated['patient_phone'],
                    'patient_national_code' =>
                        $validated['patient_national_code'] ?? null,

                    'description' =>
                        $validated['description'] ?? null,

                    'status' => 'pending',

                    'appointment_date' => $timeSlot->date,

                    'appointment_time' => $timeSlot->start_time,
                    'reservation_expires_at' => now()->addMinutes(10),
                    'visit_reason_id' => $visitReasonId,

                ]);

                $timeSlot->update([
                    'status' => 'booked',
                ]);

                session()->put('sms.visit.allowed.' . $appointment->id, now()->addMinutes(30)->timestamp);



                return redirect()->back()->with([
                    'success' => true,
                    'message' => 'نوبت با موفقیت ثبت شد.',
                    'appointment_id' => $appointment->id,
                    'fee_rial' => $appointment->fee_rial,
                ]);

            });

        } catch (\Throwable $e) {

            // اطلاعات هویتی بیمار نباید در لاگ خطا ثبت شود.
            Log::error('Appointment booking failed', [
                'time_slot_id' => $validated['time_slot_id'] ?? null,
                'exception' => get_class($e),
            ]);

            return back()->withErrors([
                'time_slot_id' =>
                    'خطایی در ثبت نوبت رخ داد. لطفاً دوباره تلاش کنید.',
            ]);
        }
    }
}