<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Address;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientEncounter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdatePatientEncounterAction
{
    public function handle(
        PatientEncounter $encounter,
        Doctor $doctor,
        array $accessibleClinicIds,
        Patient $expectedPatient,
        array $data
    ): PatientEncounter {
        return DB::transaction(function () use ($encounter, $doctor, $accessibleClinicIds, $expectedPatient, $data): PatientEncounter {
            $locked = PatientEncounter::query()
                ->whereKey($encounter->getKey())
                ->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $accessibleClinicIds)
                ->lockForUpdate()
                ->firstOrFail();

            // The clinical snapshot was prepared for this patient by the controller.
            // Reject stale or internally inconsistent encounter relationships rather
            // than attaching that snapshot to a different patient.
            if ((int) $locked->patient_id !== (int) $expectedPatient->id) {
                abort(409, 'پرونده بیمار این یادداشت تغییر کرده است؛ صفحه را تازه‌سازی کنید.');
            }

            $patient = Patient::query()
                ->whereKey($locked->patient_id)
                ->where('doctor_id', $doctor->id)
                ->firstOrFail();

            Appointment::query()
                ->whereKey($locked->appointment_id)
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->where('clinic_id', $locked->clinic_id)
                ->lockForUpdate()
                ->firstOrFail();

            Address::query()
                ->whereKey($locked->clinic_id)
                ->where('doctor_id', $doctor->id)
                ->where('is_active', true)
                ->firstOrFail();

            if ($locked->status !== PatientEncounter::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'encounter' => 'یادداشت نهایی‌شده قابل ویرایش نیست؛ برای اصلاح، الحاقیه جدید ثبت کنید.',
                ]);
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
