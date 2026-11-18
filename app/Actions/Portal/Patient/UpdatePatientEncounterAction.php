<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\PatientEncounter;
use Illuminate\Support\Facades\DB;

final class UpdatePatientEncounterAction
{
    public function handle(PatientEncounter $encounter, array $data): PatientEncounter
    {
        abort_unless($encounter->status === PatientEncounter::STATUS_DRAFT, 409, 'یادداشت امضاشده قابل ویرایش نیست.');

        return DB::transaction(function () use ($encounter, $data): PatientEncounter {
            $encounter->update([
                'note_type' => $data['note_type'],
                'subjective' => $data['subjective'] ?? null,
                'objective' => $data['objective'] ?? null,
                'assessment' => $data['assessment'],
                'plan' => $data['plan'],
                'diagnosis' => $data['diagnosis'] ?? null,
                'diagnosis_code' => $data['diagnosis_code'] ?? null,
                'reviewed_allergies' => (bool) $data['reviewed_allergies'],
                'reviewed_medications' => (bool) $data['reviewed_medications'],
                'reviewed_vitals' => (bool) $data['reviewed_vitals'],
                'clinical_snapshot' => $data['clinical_snapshot'] ?? [],
            ]);

            $encounter->prescriptions()->delete();
            foreach ($data['prescriptions'] ?? [] as $index => $prescription) {
                $encounter->prescriptions()->create($prescription + ['sort_order' => $index]);
            }

            return $encounter->load('prescriptions');
        });
    }
}