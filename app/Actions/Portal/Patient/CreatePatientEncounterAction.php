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

final class CreatePatientEncounterAction
{
    public function handle(
        Doctor $doctor,
        User $author,
        Appointment $appointment,
        array $data,
        array $accessibleClinicIds
    ): PatientEncounter {
        abort_unless((int) $appointment->doctor_id === (int) $doctor->id, 404);

        return DB::transaction(function () use ($doctor, $author, $appointment, $data, $accessibleClinicIds): PatientEncounter {
            // Re-read and lock the booking inside the transaction. Route-model
            // binding and controller checks are not sufficient against concurrent
            // changes to the patient, doctor, or clinic assignment.
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->getKey())
                ->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $accessibleClinicIds)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                (int) $lockedAppointment->patient_id !== (int) $appointment->patient_id ||
                (int) $lockedAppointment->clinic_id !== (int) $appointment->clinic_id
            ) {
                abort(409, 'اطلاعات نوبت تغییر کرده است؛ صفحه را تازه‌سازی و دوباره تلاش کنید.');
            }

            if (!$lockedAppointment->patient_id) {
                throw ValidationException::withMessages([
                    'appointment' => 'این نوبت هنوز به پرونده معتبر بیمار متصل نشده است.',
                ]);
            }

            if ($lockedAppointment->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'appointment' => 'برای نوبت لغوشده امکان ثبت یادداشت بالینی وجود ندارد.',
                ]);
            }

            Address::query()
                ->whereKey($lockedAppointment->clinic_id)
                ->where('doctor_id', $doctor->id)
                ->where('is_active', true)
                ->firstOrFail();

            $patient = Patient::query()
                ->whereKey($lockedAppointment->patient_id)
                ->where('doctor_id', $doctor->id)
                ->lockForUpdate()
                ->firstOrFail();

            $amendsEncounterId = null;
            if (!empty($data['amends_encounter_id'])) {
                $original = PatientEncounter::query()
                    ->whereKey($data['amends_encounter_id'])
                    ->where('doctor_id', $doctor->id)
                    ->where('patient_id', $patient->id)
                    ->where('appointment_id', $lockedAppointment->id)
                    ->where('clinic_id', $lockedAppointment->clinic_id)
                    ->where('status', PatientEncounter::STATUS_SIGNED)
                    ->firstOrFail();

                $amendsEncounterId = $original->id;
            }

            $encounter = PatientEncounter::query()->create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'appointment_id' => $lockedAppointment->id,
                'amends_encounter_id' => $amendsEncounterId,
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
