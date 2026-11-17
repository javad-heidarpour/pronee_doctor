<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Patient;
use App\Models\PatientClinicalRecord;
use Illuminate\Support\Facades\DB;

final class SavePatientClinicalRecordAction
{
    public function handle(
        Patient $patient,
        array $data,
        ?PatientClinicalRecord $record = null
    ): PatientClinicalRecord {
        return DB::transaction(function () use ($patient, $data, $record): PatientClinicalRecord {
            $lockedPatient = Patient::query()
                ->whereKey($patient->id)
                ->where('doctor_id', $patient->doctor_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($record) {
                $lockedRecord = PatientClinicalRecord::query()
                    ->whereKey($record->id)
                    ->where('patient_id', $lockedPatient->id)
                    ->where('doctor_id', $lockedPatient->doctor_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedRecord->update($data);

                return $lockedRecord->refresh();
            }

            return PatientClinicalRecord::query()->create([
                ...$data,
                'patient_id' => $lockedPatient->id,
                'doctor_id' => $lockedPatient->doctor_id,
            ]);
        });
    }
}
