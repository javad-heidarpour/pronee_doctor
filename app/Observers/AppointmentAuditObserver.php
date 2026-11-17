<?php

namespace App\Observers;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\Appointment;

class AppointmentAuditObserver
{
    /**
     * فقط نام فیلدهای عملیاتی مجاز ثبت می‌شوند؛
     * مقادیر شخصی یا پزشکی بیمار هرگز وارد گزارش ممیزی نمی‌شوند.
     */
    private const AUDITED_FIELDS = [
        'status',
        'doctor_id',
        'clinic_id',
        'time_slot_id',
        'appointment_date',
        'appointment_time',
        'fee_rial',
        'visit_reason_id',
        'surgery_type_id',
        'surgery_date',
    ];

    public function created(Appointment $appointment): void
    {
        app(RecordSecurityAuditEventAction::class)->handle(
            'appointment.created',
            $appointment,
            [
                'doctor_id' => $appointment->doctor_id,
                'changed_fields' => array_values(array_intersect(
                    array_keys($appointment->getAttributes()),
                    self::AUDITED_FIELDS
                )),
            ]
        );
    }

    public function updated(Appointment $appointment): void
    {
        $changedFields = array_values(array_intersect(
            array_keys($appointment->getChanges()),
            self::AUDITED_FIELDS
        ));

        if ($changedFields === []) {
            return;
        }

        app(RecordSecurityAuditEventAction::class)->handle(
            'appointment.updated',
            $appointment,
            [
                'doctor_id' => $appointment->doctor_id,
                'changed_fields' => $changedFields,
                'status_changed' => in_array('status', $changedFields, true),
            ]
        );
    }

    public function deleted(Appointment $appointment): void
    {
        app(RecordSecurityAuditEventAction::class)->handle(
            'appointment.deleted',
            $appointment,
            [
                'doctor_id' => $appointment->doctor_id,
            ]
        );
    }
}
