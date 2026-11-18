<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\PatientEncounter;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreatePatientEncounterAction
{
    public function handle(Doctor $doctor, User $author, Appointment $appointment, array $data): PatientEncounter
    {
        return DB::transaction(function () use ($doctor, $author, $appointment, $data): PatientEncounter {
            $patient = $appointment->patient;
            abort_unless($patient && (int) $patient->doctor_id === (int) $doctor->id, 422, 'پرونده معتبر بیمار برای این نوبت پیدا نشد.');

            $encounter = PatientEncounter::query()->create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'appointment_id' => $appointment->id,
                'amends_encounter_id' => $data['amends_encounter_id'] ?? null,
                'clinic_id' => $appointment->clinic_id,
                'author_id' => $author->id,
                'note_type' => $data['note_type'],
                'status' => PatientEncounter::STATUS_DRAFT,
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

            foreach ($data['prescriptions'] ?? [] as $index => $prescription) {
                $encounter->prescriptions()->create($prescription + ['sort_order' => $index]);
            }

            return $encounter->load('prescriptions');
        });
    }
}