<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Patient;
use App\Models\PatientVitalSign;
use Illuminate\Support\Facades\DB;

final class RecordPatientVitalSignAction
{
    public const UNITS = [
        'systolic_bp' => 'mmHg',
        'diastolic_bp' => 'mmHg',
        'heart_rate' => 'bpm',
        'temperature' => '°C',
        'weight' => 'kg',
        'height' => 'cm',
        'oxygen_saturation' => '%',
        'blood_glucose' => 'mg/dL',
    ];

    public function handle(Patient $patient, array $data): PatientVitalSign
    {
        return DB::transaction(function () use ($patient, $data): PatientVitalSign {
            $lockedPatient = Patient::query()
                ->whereKey($patient->id)
                ->where('doctor_id', $patient->doctor_id)
                ->lockForUpdate()
                ->firstOrFail();

            $type = $data['measurement_type'];

            return PatientVitalSign::query()->create([
                'patient_id' => $lockedPatient->id,
                'doctor_id' => $lockedPatient->doctor_id,
                'measurement_type' => $type,
                'value' => $data['value'],
                'unit' => self::UNITS[$type],
                'measured_at' => $data['measured_at'],
                'note' => $data['note'] ?? null,
            ]);
        });
    }
}
