<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientFollowUpTask;
use App\Models\User;
use Illuminate\Support\Collection;

final class ListPatient360FollowUpsAction
{
    /**
     * Return follow-ups visible in the selected doctor's accessible clinics.
     *
     * Legacy tasks without a clinic cannot be safely attributed to a secretary's
     * assigned clinic. Preserve their doctor-wide visibility for doctors/admins,
     * but never expose them to a clinic-scoped secretary.
     */
    public function handle(User $user, Doctor $doctor, Patient $patient, array $clinicIds): Collection
    {
        $clinicIds = array_values(array_unique(array_map('intval', $clinicIds)));

        if ($clinicIds === []) {
            return collect();
        }

        return PatientFollowUpTask::query()
            ->forAccessibleClinics($doctor->id, $clinicIds, $user->role !== 'secretary')
            ->where('patient_id', $patient->id)
            ->orderByRaw("CASE WHEN status IN ('pending','in_progress') THEN 0 ELSE 1 END")
            ->orderBy('due_at')
            ->limit(30)
            ->get();
    }
}
