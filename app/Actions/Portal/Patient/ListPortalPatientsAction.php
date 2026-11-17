<?php

namespace App\\Actions\\Portal\\Patient;

use App\\Models\\Appointment;
use App\\Models\\Doctor;
use Illuminate\\Contracts\\Pagination\\LengthAwarePaginator;
use Illuminate\\Support\\Facades\\DB;

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

        // MAX(id) is not necessarily the latest visit when appointments are backdated
        // or imported. Resolve the actual latest appointment by date, time, then id.
        $latestAppointmentId = DB::table('appointments as latest_appointment')
            ->select('latest_appointment.id')
            ->whereColumn('latest_appointment.doctor_id', 'appointments.doctor_id')
            ->whereIn('latest_appointment.clinic_id', $clinicIds)
            ->whereRaw(
                "COALESCE(NULLIF(latest_appointment.patient_national_code, ''), NULLIF(latest_appointment.patient_phone, '')) = {$identity}"
            )
            ->orderByDesc('latest_appointment.appointment_date')
            ->orderByDesc('latest_appointment.appointment_time')
            ->orderByDesc('latest_appointment.id')
            ->limit(1);

        return $query
            ->selectRaw("{$identity} as patient_key")
            ->selectRaw('MAX(appointments.patient_name) as patient_name')
            ->selectRaw('MAX(appointments.patient_phone) as patient_phone')
            ->selectRaw('MAX(appointments.patient_national_code) as patient_national_code')
            ->selectRaw('COUNT(*) as appointments_count')
            ->selectRaw('MAX(appointments.appointment_date) as last_appointment_date')
            ->selectSub($latestAppointmentId, 'latest_appointment_id')
            ->groupByRaw($identity)
            ->orderByDesc('last_appointment_date')
            ->paginate(20)
            ->withQueryString();
    }
}
