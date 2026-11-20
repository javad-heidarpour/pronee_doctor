<?php

namespace App\Actions\Portal\Patient;

use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ListPortalPatientsAction
{
    public function handle(Doctor $doctor, array $clinicIds, ?string $search = null): LengthAwarePaginator
    {
        // Prefer the stable doctor-scoped patient id. Legacy appointments fall
        // back to national code, then phone, without merging phone-only records
        // into patients who have a national code.
        $sqlite = DB::connection()->getDriverName() === 'sqlite';
        $patientKey = $sqlite
            ? "('patient:' || CAST(appointments.patient_id AS TEXT))"
            : "CONCAT('patient:', appointments.patient_id)";
        $nationalCodeKey = $sqlite
            ? "('national:' || appointments.patient_national_code)"
            : "CONCAT('national:', appointments.patient_national_code)";
        $phoneKey = $sqlite
            ? "('phone:' || appointments.patient_phone)"
            : "CONCAT('phone:', appointments.patient_phone)";

        $identity = "CASE
            WHEN appointments.patient_id IS NOT NULL THEN {$patientKey}
            WHEN NULLIF(appointments.patient_national_code, '') IS NOT NULL THEN {$nationalCodeKey}
            ELSE {$phoneKey}
        END";

        $query = Appointment::query()
            ->where('appointments.doctor_id', $doctor->id)
            ->whereIn('appointments.clinic_id', $clinicIds)
            ->where(function ($builder) {
                $builder->whereNotNull('appointments.patient_id')
                    ->orWhere(function ($nationalCode) {
                        $nationalCode->whereNotNull('appointments.patient_national_code')
                            ->where('appointments.patient_national_code', '<>', '');
                    })
                    ->orWhere(function ($phone) {
                        $phone->whereNotNull('appointments.patient_phone')
                            ->where('appointments.patient_phone', '<>', '');
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
            ->selectRaw("{$identity} as patient_key")
            ->selectRaw('MAX(appointments.patient_id) as patient_id')
            ->selectRaw('MAX(appointments.patient_name) as patient_name')
            ->selectRaw('MAX(appointments.patient_phone) as patient_phone')
            ->selectRaw('MAX(appointments.patient_national_code) as patient_national_code')
            ->selectRaw('COUNT(*) as appointments_count')
            ->selectRaw('MAX(appointments.appointment_date) as last_appointment_date')
            ->groupByRaw($identity)
            ->orderByDesc('last_appointment_date')
            ->paginate(20)
            ->withQueryString();

        // Resolve the latest visit separately to keep GROUP BY portable and
        // show the latest identity values rather than lexical MAX values.
        $patients->getCollection()->transform(function ($patient) use ($doctor, $clinicIds) {
            $latestVisitQuery = Appointment::query()
                ->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $clinicIds);

            if ($patient->patient_id !== null) {
                $latestVisitQuery->where('patient_id', $patient->patient_id);
            } else {
                $nationalCode = trim((string) $patient->patient_national_code);
                $phone = trim((string) $patient->patient_phone);

                if ($nationalCode !== '') {
                    $latestVisitQuery->where('patient_national_code', $nationalCode);
                } else {
                    $latestVisitQuery
                        ->whereNull('patient_national_code')
                        ->where('patient_phone', $phone);
                }
            }

            $latestVisit = $latestVisitQuery
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->orderByDesc('id')
                ->first([
                    'id',
                    'patient_name',
                    'patient_phone',
                    'patient_national_code',
                ]);

            $patient->latest_appointment_id = $latestVisit?->id;

            if ($latestVisit) {
                $patient->patient_name = $latestVisit->patient_name;
                $patient->patient_phone = $latestVisit->patient_phone;
                $patient->patient_national_code = $latestVisit->patient_national_code;
            }

            return $patient;
        });

        return $patients;
    }
}
