<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Appointment\CreatePortalAppointmentAction;
use App\Actions\Portal\Appointment\ListAvailableSlotsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\AvailableSlotsRequest;
use App\Http\Requests\Portal\StorePortalAppointmentRequest;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    /**
     * دریافت تمام ساعت‌های آزاد آینده یک مطب
     */
    public function availableSlots(AvailableSlotsRequest $request, ListAvailableSlotsAction $action)
    {
        $doctor = Auth::guard('web')->user()->doctorProfile;

        abort_unless($doctor, 401);

        $slots = $action->handle($doctor, $request->validated()['clinic_id']);

        if ($slots === null) {
            return response()->json([
                'message' => 'مطب انتخاب‌شده معتبر نیست.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'slots' => $slots,
        ]);
    }

    /**
     * ثبت نوبت توسط منشی، بدون درگاه پرداخت
     */
    public function store(StorePortalAppointmentRequest $request, CreatePortalAppointmentAction $action)
    {
        $doctor = Auth::guard('web')->user()->doctorProfile;

        abort_unless($doctor, 401);

        $appointment = $action->handle($doctor, $request->validated());

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
    }
}
