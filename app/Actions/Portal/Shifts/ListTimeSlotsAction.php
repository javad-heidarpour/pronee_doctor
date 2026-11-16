<?php

namespace App\Actions\Portal\Shifts;

use App\Models\Doctor;
use App\Models\TimeSlot;
use Illuminate\Http\Request;

class ListTimeSlotsAction
{
    public function handle(Doctor $doctor, Request $request, ?array $allowedClinicIds = null): array
    {
        $clinics = $doctor->address()->whereIn('addresses.id', $allowedClinicIds ?? $doctor->address()->pluck('addresses.id'))->with('city')->get();

        $query = TimeSlot::query()
            ->where('doctor_id', $doctor->id)
            ->when($allowedClinicIds !== null, fn ($q) => $q->whereIn('clinic_id', $allowedClinicIds))
            ->with('clinic.city');

        if ($request->filled('clinic_id')) {
            $query->where('clinic_id', $request->input('clinic_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($builder) use ($search) {
                $builder->where('date', 'LIKE', "%{$search}%")
                    ->orWhere('start_time', 'LIKE', "%{$search}%");
            });
        }

        $allowedSortColumns = [
            'date', 'start_time', 'end_time', 'status', 'created_at',
        ];

        $sortBy = $request->input('sort_by', 'date');
        if (!in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'date';
        }

        $sortDir = strtolower($request->input('sort_dir', 'asc'));
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $slots = $query
            ->orderBy($sortBy, $sortDir)
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        $statsQuery = TimeSlot::query()->where('doctor_id', $doctor->id)
            ->when($allowedClinicIds !== null, fn ($q) => $q->whereIn('clinic_id', $allowedClinicIds));

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'available' => (clone $statsQuery)->where('status', 'available')->count(),
            'booked' => (clone $statsQuery)->where('status', 'booked')->count(),
            'cancelled' => (clone $statsQuery)->where('status', 'cancelled')->count(),
        ];

        return [
            'doctor' => $doctor,
            'clinics' => $clinics,
            'slots' => $slots,
            'stats' => $stats,
            'filters' => $request->only([
                'clinic_id', 'status', 'date_from', 'date_to',
                'search', 'sort_by', 'sort_dir',
            ]),
        ];
    }
}