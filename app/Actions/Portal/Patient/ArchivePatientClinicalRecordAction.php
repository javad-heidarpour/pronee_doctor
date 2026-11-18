<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Patient;
use App\Models\PatientClinicalRecord;
use Illuminate\Support\Facades\DB;

final class ArchivePatientClinicalRecordAction
{
    public function handle(Patient $patient, PatientClinicalRecord $record): void
    {
        DB::transaction(function () use ($patient, $record): void {
            $lockedPatient = Patient::query()
                ->whereKey($patient->id)
                ->where('doctor_id', $patient->doctor_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedRecord = PatientClinicalRecord::query()
                ->whereKey($record->id)
                ->where('patient_id', $lockedPatient->id)
                ->where('doctor_id', $lockedPatient->doctor_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedRecord->delete();
        });
    }
}
