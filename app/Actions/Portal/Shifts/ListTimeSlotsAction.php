<?php

namespace App\Actions\Portal\Shifts;

use App\Models\Doctor;
use App\Models\TimeSlot;

class ListTimeSlotsAction
{
    public function handle(Doctor $doctor, array $filters, ?array $allowedClinicIds = null): array
    {
        $clinics = $doctor->address()->whereIn('addresses.id', $allowedClinicIds ?? $doctor->address()->pluck('addresses.id'))->with('city')->get();

        $query = TimeSlot::query()
            ->where('doctor_id', $doctor->id)
            ->when($allowedClinicIds !== null, fn ($q) => $q->whereIn('clinic_id', $allowedClinicIds))
            ->with('clinic.city');

        if (!empty($filters['clinic_id'])) {
            $query->where('clinic_id', $filters['clinic_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($builder) use ($search) {
                $builder->where('date', 'LIKE', "%{$search}%")
                    ->orWhere('start_time', 'LIKE', "%{$search}%");
            });
        }

        $allowedSortColumns = [
            'date', 'start_time', 'end_time', 'status', 'created_at',
        ];

        $sortBy = ($filters['sort_by'] ?? 'date');
        if (!in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'date';
        }

        $sortDir = strtolower(($filters['sort_dir'] ?? 'asc'));
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
            'filters' => $filters,
        ];
    }
}