<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientEncounter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SignPatientEncounterAction
{
    public function handle(PatientEncounter $encounter, User $signer): PatientEncounter
    {
        return DB::transaction(function () use ($encounter, $signer): PatientEncounter {
            $locked = PatientEncounter::query()->whereKey($encounter->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PatientEncounter::STATUS_DRAFT) {
                throw ValidationException::withMessages(['encounter' => 'این یادداشت قبلاً نهایی شده است.']);
            }

            $appointment = Appointment::query()
                ->whereKey($locked->appointment_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($appointment->status === 'cancelled') {
                throw ValidationException::withMessages(['encounter' => 'برای نوبت لغوشده امکان نهایی‌سازی ویزیت وجود ندارد.']);
            }

            if (
                (int) $appointment->doctor_id !== (int) $locked->doctor_id ||
                (int) $appointment->patient_id !== (int) $locked->patient_id ||
                (int) $appointment->clinic_id !== (int) $locked->clinic_id ||
                !Patient::query()->whereKey($locked->patient_id)
                    ->where('doctor_id', $locked->doctor_id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'encounter' => 'ارتباط نوبت، بیمار و پرونده ویزیت معتبر نیست؛ امکان نهایی‌سازی وجود ندارد.',
                ]);
            }

            if (!$locked->reviewed_allergies || !$locked->reviewed_medications || !$locked->reviewed_vitals) {
                throw ValidationException::withMessages(['reviewed_allergies' => 'برای نهایی‌سازی، بازبینی حساسیت‌ها، داروها و علائم حیاتی را تأیید کنید.']);
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