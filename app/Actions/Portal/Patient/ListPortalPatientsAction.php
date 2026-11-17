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
        $identity = "COALESCE(NULLIF(appointments.patient_national_code, ''), NULLIF(appointments.patient_phone, ''))";

        $query = Appointment::query()
            ->where('appointments.doctor_id', $doctor->id)
            ->whereIn('appointments.clinic_id', $clinicIds)
            ->where(function ($builder) {
                $builder->whereNotNull('appointments.patient_national_code')
                    ->where('appointments.patient_national_code', '<>', '')
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
            ->selectRaw('MAX(appointments.patient_name) as patient_name')
            ->selectRaw('MAX(appointments.patient_phone) as patient_phone')
            ->selectRaw('MAX(appointments.patient_national_code) as patient_national_code')
            ->selectRaw('COUNT(*) as appointments_count')
            ->selectRaw('MAX(appointments.appointment_date) as last_appointment_date')
            ->groupByRaw($identity)
            ->orderByDesc('last_appointment_date')
            ->paginate(20)
            ->withQueryString();

        // Resolve the actual latest visit per visible patient. Keeping this lookup
        // outside the aggregate query avoids GROUP BY portability issues on MySQL.
        $patients->getCollection()->transform(function ($patient) use ($doctor, $clinicIds) {
            $latestVisitQuery = Appointment::query()
                ->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $clinicIds);

            $nationalCode = trim((string) $patient->patient_national_code);
            $phone = trim((string) $patient->patient_phone);

            if ($nationalCode !== '') {
                $latestVisitQuery->where('patient_national_code', $nationalCode);
            } else {
                $latestVisitQuery->where('patient_phone', $phone);
            }

            $patient->latest_appointment_id = $latestVisitQuery
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->orderByDesc('id')
                ->value('id');

            return $patient;
        });

        return $patients;
    }
}
