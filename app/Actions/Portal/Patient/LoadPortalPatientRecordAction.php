<?php

namespace App\Actions\Portal\Patient;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Collection;

class LoadPortalPatientRecordAction
{
    /**
     * Load the complete visit timeline for the patient represented by a scoped appointment.
     * Patient identifiers are never accepted from the URL or request payload.
     */
    public function handle(Appointment $reference, array $accessibleClinicIds): Collection
    {
        $nationalCode = trim((string) $reference->patient_national_code);
        $phone = trim((string) $reference->patient_phone);

        abort_if($nationalCode === '' && $phone === '', 404);

        $query = Appointment::query()
            ->where('doctor_id', $reference->doctor_id)
            ->whereIn('clinic_id', $accessibleClinicIds)
            ->with(['clinic', 'visitReason', 'latestPayment', 'surgeryType']);

        // National code is the primary identity. Phone is only a fallback when
        // no national code was captured for the reference appointment.
        if ($nationalCode !== '') {
            $query->where('patient_national_code', $nationalCode);
        } else {
            $query->where('patient_phone', $phone);
        }

        return $query
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->orderByDesc('id')
            ->get();
    }
}
