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
            ->with(['patient', 'clinic', 'visitReason', 'latestPayment', 'surgeryType']);

        if ($reference->patient_id !== null) {
            $query->where('patient_id', $reference->patient_id);
        } else {
            // Compatibility while legacy appointments are being backfilled.
            $nationalCode = trim((string) $reference->patient_national_code);
            $phone = trim((string) $reference->patient_phone);

            abort_if($nationalCode === '' && $phone === '', 404);

            if ($nationalCode !== '') {
                $query->where('patient_national_code', $nationalCode);
            } else {
                $query->whereNull('patient_id')->where('patient_phone', $phone);
            }
        }

        return $query
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->orderByDesc('id')
            ->get();
    }
}
