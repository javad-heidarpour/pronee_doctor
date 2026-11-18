<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Appointment;
use App\Models\PatientEncounter;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

final class SignPatientEncounterAction
{
    public function handle(PatientEncounter $encounter, User $signer): PatientEncounter
    {
        if ($encounter->status !== PatientEncounter::STATUS_DRAFT) {
            throw ValidationException::withMessages(['encounter' => 'این یادداشت قبلاً نهایی شده است.']);
        }

        if (!$encounter->reviewed_allergies || !$encounter->reviewed_medications || !$encounter->reviewed_vitals) {
            throw ValidationException::withMessages(['reviewed_allergies' => 'برای نهایی‌سازی، بازبینی حساسیت‌ها، داروها و علائم حیاتی را تأیید کنید.']);
        }

        return DB::transaction(function () use ($encounter, $signer): PatientEncounter {
            $encounter->update([
                'status' => PatientEncounter::STATUS_SIGNED,
                'signed_at' => now(),
                'signed_by' => $signer->id,
            ]);

            Appointment::query()->whereKey($encounter->appointment_id)
                ->where('doctor_id', $encounter->doctor_id)
                ->where('status', '!=', 'cancelled')
                ->update(['status' => 'visited', 'updated_at' => now()]);

            return $encounter->fresh(['prescriptions', 'author', 'signer', 'clinic']);
        });
    }
}