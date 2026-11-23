<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Address;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientEncounter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SignPatientEncounterAction
{
    public function handle(
        PatientEncounter $encounter,
        User $signer,
        Doctor $doctor,
        array $accessibleClinicIds
    ): PatientEncounter {
        return DB::transaction(function () use ($encounter, $signer, $doctor, $accessibleClinicIds): PatientEncounter {
            // Re-check the tenant and clinic boundary after acquiring the row lock.
            $locked = PatientEncounter::query()
                ->whereKey($encounter->getKey())
                ->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $accessibleClinicIds)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== PatientEncounter::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'encounter' => 'این یادداشت قبلاً نهایی شده است.',
                ]);
            }

            $patient = Patient::query()
                ->whereKey($locked->patient_id)
                ->where('doctor_id', $doctor->id)
                ->firstOrFail();

            Address::query()
                ->whereKey($locked->clinic_id)
                ->where('doctor_id', $doctor->id)
                ->where('is_active', true)
                ->firstOrFail();

            $appointment = Appointment::query()
                ->whereKey($locked->appointment_id)
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->where('clinic_id', $locked->clinic_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($appointment->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'encounter' => 'برای نوبت لغوشده امکان نهایی‌سازی ویزیت وجود ندارد.',
                ]);
            }

            if (!$locked->reviewed_allergies || !$locked->reviewed_medications || !$locked->reviewed_vitals) {
                throw ValidationException::withMessages([
                    'reviewed_allergies' => 'برای نهایی‌سازی، بازبینی حساسیت‌ها، داروها و علائم حیاتی را تأیید کنید.',
                ]);
            }

            $locked->update([
                'status' => PatientEncounter::STATUS_SIGNED,
                'signed_at' => now(),
                'signed_by' => $signer->id,
            ]);

            $appointment->update(['status' => 'visited']);

            return $locked->fresh(['prescriptions', 'author', 'signer', 'clinic']);
        });
    }
}
