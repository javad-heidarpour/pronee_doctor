<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Patient;
use App\Models\PatientEncounter;
use App\Models\PatientProblem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SavePatientProblemAction
{
    public function handle(
        Patient $patient,
        User $actor,
        array $data,
        ?PatientProblem $problem = null,
        ?array $accessibleClinicIds = null
    ): PatientProblem {
        return DB::transaction(function () use ($patient, $actor, $data, $problem, $accessibleClinicIds): PatientProblem {
            $lockedPatient = Patient::query()
                ->whereKey($patient->id)
                ->where('doctor_id', $patient->doctor_id)
                ->lockForUpdate()
                ->firstOrFail();

            $encounterId = $data['encounter_id'] ?? null;
            if ($encounterId !== null) {
                $encounterQuery = PatientEncounter::query()
                    ->whereKey($encounterId)
                    ->where('doctor_id', $lockedPatient->doctor_id)
                    ->where('patient_id', $lockedPatient->id)
                    ->where('status', PatientEncounter::STATUS_SIGNED);

                if ($accessibleClinicIds !== null) {
                    $encounterQuery->whereIn('clinic_id', $accessibleClinicIds);
                }

                $encounter = $encounterQuery->lockForUpdate()->first();

                if (!$encounter) {
                    throw ValidationException::withMessages([
                        'encounter_id' => 'ویزیت انتخاب‌شده متعلق به این بیمار نیست یا هنوز نهایی نشده است.',
                    ]);
                }
            }

            $status = $data['status'] ?? PatientProblem::STATUS_ACTIVE;
            $resolvedOn = $status === PatientProblem::STATUS_RESOLVED
                ? ($data['resolved_on'] ?? now()->toDateString())
                : null;

            $attributes = [
                'encounter_id' => $encounterId,
                'category' => $data['category'],
                'title' => $data['title'],
                'code' => $data['code'] ?? null,
                'code_system' => $data['code_system'] ?? 'local',
                'description' => $data['description'] ?? null,
                'status' => $status,
                'severity' => $data['severity'] ?? null,
                'onset_date' => $data['onset_date'] ?? null,
                'resolved_on' => $resolvedOn,
                'source' => $data['source'] ?? 'clinician_assessed',
                'updated_by' => $actor->id,
            ];

            if ($problem) {
                $lockedProblem = PatientProblem::query()
                    ->whereKey($problem->id)
                    ->where('patient_id', $lockedPatient->id)
                    ->where('doctor_id', $lockedPatient->doctor_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedProblem->update($attributes);

                return $lockedProblem->refresh();
            }

            return PatientProblem::query()->create([
                ...$attributes,
                'patient_id' => $lockedPatient->id,
                'doctor_id' => $lockedPatient->doctor_id,
                'created_by' => $actor->id,
            ]);
        });
    }
}