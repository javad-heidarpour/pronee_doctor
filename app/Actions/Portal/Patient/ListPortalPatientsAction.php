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
        $identity = "COALESCE(NULLIF(patient_national_code, ''), NULLIF(patient_phone, ''))";

        $query = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('clinic_id', $clinicIds)
            ->where(function ($builder) {
                $builder->whereNotNull('patient_national_code')
                    ->where('patient_national_code', '<>', '')
                    ->orWhere(function ($phone) {
                        $phone->whereNotNull('patient_phone')->where('patient_phone', '<>', '');
                    });
            });

        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $query->where(function ($builder) use ($term) {
                $builder->where('patient_name', 'like', "%{$term}%")
                    ->orWhere('patient_phone', 'like', "%{$term}%")
                    ->orWhere('patient_national_code', 'like', "%{$term}%");
            });
        }

        return $query
            ->selectRaw("{$identity} as patient_key")
            ->selectRaw('MAX(patient_name) as patient_name')
            ->selectRaw('MAX(patient_phone) as patient_phone')
            ->selectRaw('MAX(patient_national_code) as patient_national_code')
            ->selectRaw('COUNT(*) as appointments_count')
            ->selectRaw('MAX(appointment_date) as last_appointment_date')
            ->selectRaw('MAX(id) as latest_appointment_id')
            ->groupByRaw($identity)
            ->orderByDesc('last_appointment_date')
            ->paginate(20)
            ->withQueryString();
    }
}
