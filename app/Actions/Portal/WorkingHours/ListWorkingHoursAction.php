<?php

namespace App\Actions\Portal\WorkingHours;

use App\Models\Doctor;
use App\Models\DoctorWorkingHour;
use Illuminate\Http\Request;

class ListWorkingHoursAction
{
    public function handle(Doctor $doctor, Request $request): array
    {
        $clinics = $doctor->address()->with('city')->get();

        $query = DoctorWorkingHour::query()
            ->where('doctor_id', $doctor->id)
            ->with('clinic.city');

        if ($request->filled('clinic_id')) {
            $query->where('clinic_id', $request->input('clinic_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active') === 'true');
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

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

        $sortBy = $request->input('sort_by', 'day_of_week');
        if (!in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'day_of_week';
        }

        $sortDir = strtolower($request->input('sort_dir', 'asc'));
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
            'filters' => $request->only([
                'clinic_id', 'is_active', 'search', 'sort_by', 'sort_dir',
            ]),
        ];
    }
}