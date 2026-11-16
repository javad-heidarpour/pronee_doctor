<?php

namespace App\\Http\\Controllers\\Portal;

use App\\Actions\\Portal\\Appointment\\CreatePortalAppointmentAction;
use App\\Actions\\Portal\\Appointment\\ListAvailableSlotsAction;
use App\\Http\\Controllers\\Controller;
use App\\Http\\Controllers\\Portal\\Concerns\\HasActiveDoctor;
use App\\Http\\Requests\\Portal\\AvailableSlotsRequest;
use App\\Http\\Requests\\Portal\\StorePortalAppointmentRequest;

class AppointmentController extends Controller
{
    use HasActiveDoctor;

    /**
     * دریافت تمام ساعت‌های آزاد آینده یک مطب
     */
    public function availableSlots(
        AvailableSlotsRequest $request,
        ListAvailableSlotsAction $action
    ) {
        $doctor = $this->currentDoctor();

        $slots = $action->handle(
            $doctor,
            $request->validated()['clinic_id']
        );

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
     * ثبت نوبت توسط پزشک یا منشی، بدون درگاه پرداخت
     */
    public function store(
        StorePortalAppointmentRequest $request,
        CreatePortalAppointmentAction $action
    ) {
        $doctor = $this->currentDoctor();

        $appointment = $action->handle(
            $doctor,
            $request->validated()
        );

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