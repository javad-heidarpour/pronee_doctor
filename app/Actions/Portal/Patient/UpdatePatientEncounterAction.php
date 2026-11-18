<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\PatientEncounter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdatePatientEncounterAction
{
    public function handle(PatientEncounter $encounter, array $data): PatientEncounter
    {
        return DB::transaction(function () use ($encounter, $data): PatientEncounter {
            $locked = PatientEncounter::query()->whereKey($encounter->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== PatientEncounter::STATUS_DRAFT) {
                throw ValidationException::withMessages(['encounter' => 'یادداشت نهایی‌شده قابل ویرایش نیست؛ برای اصلاح، الحاقیه جدید ثبت کنید.']);
            }

            $locked->update([
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

            $locked->prescriptions()->delete();
            foreach ($data['prescriptions'] ?? [] as $index => $prescription) {
                $locked->prescriptions()->create($prescription + ['sort_order' => $index]);
            }

            return $locked->load('prescriptions');
        });
    }
}