<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Patient;
use App\Models\PatientClinicalRecord;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

            $relatedRecordId = $data['related_record_id'] ?? null;
            if ($relatedRecordId !== null) {
                $relatedRecord = PatientClinicalRecord::query()
                    ->whereKey($relatedRecordId)
                    ->where('patient_id', $lockedPatient->id)
                    ->where('doctor_id', $lockedPatient->doctor_id)
                    ->where('kind', PatientClinicalRecord::KIND_MEDICATION)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (!$relatedRecord ||
                    ($record && (int) $record->id === (int) $relatedRecordId) ||
                    ($data['kind'] ?? null) !== PatientClinicalRecord::KIND_ADVERSE_REACTION) {
                    throw new ModelNotFoundException('Related medication record not found.');
                }
            }

            $relatedAllergyId = $data['related_allergy_record_id'] ?? null;
            if ($relatedAllergyId !== null) {
                $relatedAllergy = PatientClinicalRecord::query()
                    ->whereKey($relatedAllergyId)
                    ->where('patient_id', $lockedPatient->id)
                    ->where('doctor_id', $lockedPatient->doctor_id)
                    ->where('kind', PatientClinicalRecord::KIND_ALLERGY)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (!$relatedAllergy ||
                    ($record && (int) $record->id === (int) $relatedAllergyId) ||
                    ($data['kind'] ?? null) !== PatientClinicalRecord::KIND_ADVERSE_REACTION) {
                    throw new ModelNotFoundException('Related allergy record not found.');
                }
            }

            if (($data['kind'] ?? null) !== PatientClinicalRecord::KIND_ADVERSE_REACTION) {
                $data['related_record_id'] = null;
                $data['related_allergy_record_id'] = null;
            }

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
