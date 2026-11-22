<?php

namespace App\Actions\Portal\Patient;

use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPortalPatientsAction
{
    public function handle(Doctor $doctor, array $clinicIds, ?string $search = null): LengthAwarePaginator
    {
        // A stable patient key is derived from the strongest identifier available.
        // This provides a directory without migrating or duplicating legacy appointment data.
        $identityType = "CASE
            WHEN appointments.patient_id IS NOT NULL THEN 'patient'
            WHEN NULLIF(appointments.patient_national_code, '') IS NOT NULL THEN 'national_code'
            ELSE 'appointment'
        END";
        $identityValue = "CASE
            WHEN appointments.patient_id IS NOT NULL THEN CAST(appointments.patient_id AS CHAR)
            WHEN NULLIF(appointments.patient_national_code, '') IS NOT NULL THEN appointments.patient_national_code
            ELSE CAST(appointments.id AS CHAR)
        END";

        $query = Appointment::query()
            ->where('appointments.doctor_id', $doctor->id)
            ->whereIn('appointments.clinic_id', $clinicIds)
            ->where(function ($builder) {
                $builder->whereNotNull('appointments.patient_id')
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('appointments.patient_id')
                            ->where(function ($identifiers) {
                                $identifiers->whereNotNull('appointments.patient_national_code')
                                    ->where('appointments.patient_national_code', '<>', '')
                                    ->orWhere(function ($phone) {
                                        $phone->whereNotNull('appointments.patient_phone')
                                            ->where('appointments.patient_phone', '<>', '');
                                    });
                            });
                    });
            });

        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $query->where(function ($builder) use ($term) {
                $builder->where('appointments.patient_name', 'like', "%{$term}%")
                    ->orWhere('appointments.patient_phone', 'like', "%{$term}%")
                    ->orWhere('appointments.patient_national_code', 'like', "%{$term}%");
            });
        }

        $patients = $query
            ->selectRaw("{$identityType} as patient_key_type")
            ->selectRaw("{$identityValue} as patient_key_value")
            ->selectRaw('MAX(appointments.patient_id) as patient_id')
            ->selectRaw('MAX(appointments.patient_national_code) as patient_national_code')
            ->selectRaw('MAX(appointments.id) as fallback_appointment_id')
            ->selectRaw('MAX(appointments.patient_name) as patient_name')
            ->selectRaw('MAX(appointments.patient_phone) as patient_phone')
            ->selectRaw('MAX(appointments.patient_national_code) as patient_national_code')
            ->selectRaw('COUNT(*) as appointments_count')
            ->selectRaw('MAX(appointments.appointment_date) as last_appointment_date')
            ->groupByRaw("{$identityType}, {$identityValue}")
            ->orderByDesc('last_appointment_date')
            ->paginate(20)
            ->withQueryString();

        // Resolve the actual latest visit per visible patient. Keeping this lookup
        // outside the aggregate query avoids GROUP BY portability issues on MySQL.
        $patients->getCollection()->transform(function ($patient) use ($doctor, $clinicIds) {
            $patient->patient_key = $patient->patient_key_type . ':' . $patient->patient_key_value;

            $latestVisitQuery = Appointment::query()
                ->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $clinicIds);

            $patientId = $patient->patient_id !== null ? (int) $patient->patient_id : null;
            $nationalCode = trim((string) $patient->patient_national_code);

            if ($patientId !== null) {
                $latestVisitQuery->where('patient_id', $patientId);
            } elseif ($nationalCode !== '') {
                $latestVisitQuery->whereNull('patient_id')
                    ->where('patient_national_code', $nationalCode);
            } else {
                // Phone-only legacy visits are separate directory entries. Never
                // infer that two people sharing a household phone are one patient.
                $latestVisitQuery->whereKey((int) $patient->fallback_appointment_id);
            }

            $patient->latest_appointment_id = $latestVisitQuery
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->orderByDesc('id')
                ->value('id');

            $patient->makeHidden([
                'patient_key_type',
                'patient_key_value',
                'patient_id',
                'fallback_appointment_id',
            ]);

            return $patient;
        });

        return $patients;
    }
}
