<?php

namespace App\Actions\Portal\WorkingHours;

use App\Models\Doctor;
use App\Models\DoctorWorkingHour;

class ListWorkingHoursAction
{
    public function handle(Doctor $doctor, array $filters = [], ?array $allowedClinicIds = null): array
    {
        $clinics = $doctor->address()->whereIn('addresses.id', $allowedClinicIds ?? $doctor->address()->pluck('addresses.id'))->with('city')->get();

        $query = DoctorWorkingHour::query()
            ->where('doctor_id', $doctor->id)
            ->when($allowedClinicIds !== null, fn ($q) => $q->whereIn('clinic_id', $allowedClinicIds))
            ->with('clinic.city');

        if (array_key_exists('clinic_id', $filters) && $filters['clinic_id'] !== '') {
            $query->where('clinic_id', $filters['clinic_id']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '') {
            $query->where('is_active', in_array($filters['is_active'], [true, 1, '1', 'true'], true));
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->whereHas('clinic', function ($clinicQuery) use ($search) {
                $clinicQuery->where('name', 'LIKE', "%{$search}%")
                    ->orWhereHas('city', function ($cityQuery) use ($search) {
                        $cityQuery->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        $allowedSortColumns = [
            'day_of_week', 'start_time', 'end_time', 'duration_minutes',
            'is_active', 'clinic_id', 'created_at',
        ];

        $sortBy = $filters['sort_by'] ?? 'day_of_week';
        if (!in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'day_of_week';
        }

        $sortDir = strtolower($filters['sort_dir'] ?? 'asc');
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $workingHours = $query
            ->orderBy($sortBy, $sortDir)
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        return [
            'doctor' => $doctor,
            'clinics' => $clinics,
            'workingHours' => $workingHours,
            'filters' => array_intersect_key($filters, array_flip([
                'clinic_id', 'is_active', 'search', 'sort_by', 'sort_dir',
            ])),
        ];
    }
}