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
            ->where('doctor_id', $reference->doctor_id)
            ->whereIn('clinic_id', $accessibleClinicIds)
            ->with([
                'patient' => fn ($patientQuery) => $patientQuery
                    ->where('doctor_id', $reference->doctor_id),
                'clinic',
                'visitReason',
                'latestPayment',
                'surgeryType',
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
