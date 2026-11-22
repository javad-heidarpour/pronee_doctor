<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Collection;

final class LoadPortalPatientRecordAction
{
    /**
     * Load the visit timeline for the patient represented by a scoped appointment.
     * The patient's identifiers are never accepted from the URL or request payload.
     */
    public function handle(Appointment $reference, array $accessibleClinicIds): Collection
    {
        $query = Appointment::query()
            // Only serialize fields used by the patient timeline. In particular,
            // do not send account, slot, or unrelated booking metadata to Inertia.
            ->select([
                'appointments.id',
                'appointments.doctor_id',
                'appointments.patient_id',
                'appointments.clinic_id',
                'appointments.patient_name',
                'appointments.patient_phone',
                'appointments.patient_national_code',
                'appointments.status',
                'appointments.appointment_date',
                'appointments.appointment_time',
                'appointments.description',
                'appointments.visit_reason_id',
                'appointments.surgery_type_id',
                'appointments.surgery_date',
            ])
            ->where('appointments.doctor_id', $reference->doctor_id)
            ->whereIn('appointments.clinic_id', $accessibleClinicIds)
            ->with([
                'patient' => fn ($patientQuery) => $patientQuery
                    ->select(['patients.id', 'patients.doctor_id', 'patients.name', 'patients.phone', 'patients.national_code'])
                    ->where('doctor_id', $reference->doctor_id),
                'clinic:id,name',
                'visitReason:id,title',
                'latestPayment' => fn ($paymentQuery) => $paymentQuery->select([
                    'appointment_payments.id',
                    'appointment_payments.appointment_id',
                    'appointment_payments.status',
                ]),
                'surgeryType:id,title',
            ]);

        if ($reference->patient_id !== null) {
            $query->where('patient_id', $reference->patient_id);
        } else {
            // Legacy rows may be grouped by national code only. A phone number
            // can be shared by family members and is not a safe patient key.
            $nationalCode = trim((string) $reference->patient_national_code);

            if ($nationalCode !== '') {
                $query->whereNull('patient_id')
                    ->where('patient_national_code', $nationalCode);
            } else {
                // Without a stable patient identifier, expose only this visit.
                $query->whereKey($reference->id);
            }
        }

        return $query
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->orderByDesc('id')
            ->get();
    }
}
