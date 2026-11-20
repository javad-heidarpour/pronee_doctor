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
        abort_unless((int) $appointment->doctor_id === (int) $doctor->id, 404);

        return DB::transaction(function () use ($doctor, $author, $appointment, $data): PatientEncounter {
            // Lock and re-read the appointment so a concurrent cancellation or
            // patient reassignment cannot create a note against stale data.
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless((int) $lockedAppointment->doctor_id === (int) $doctor->id, 404);
            if ($lockedAppointment->status === 'cancelled') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'appointment' => 'برای نوبت لغوشده امکان ثبت یادداشت ویزیت وجود ندارد.',
                ]);
            }

            $patient = \App\Models\Patient::query()
                ->whereKey($lockedAppointment->patient_id)
                ->where('doctor_id', $doctor->id)
                ->lockForUpdate()
                ->first();

            abort_unless($patient, 422, 'پرونده معتبر بیمار برای این نوبت پیدا نشد.');

            $encounter = PatientEncounter::query()->create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'appointment_id' => $lockedAppointment->id,
                'amends_encounter_id' => $data['amends_encounter_id'] ?? null,
                'clinic_id' => $lockedAppointment->clinic_id,
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