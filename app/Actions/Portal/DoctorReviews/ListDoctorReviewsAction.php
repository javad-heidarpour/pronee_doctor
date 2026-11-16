<?php

namespace App\Actions\Portal\DoctorReviews;

use App\Models\Doctor;
use App\Models\Review;
use Inertia\Inertia;

class ListDoctorReviewsAction
{
    public function handle(array $filters, Doctor $doctor)
    {
        $reviews = Review::query()
            ->where('doctor_id', $doctor->id)
            ->when(!empty($filters['search']), function ($query) use ($request) {
                $search = trim(trim((string) $filters['search']));

                $query->where(function ($q) use ($search) {
                    $q->where('patient_name', 'like', "%{$search}%")
                        ->orWhere('comment', 'like', "%{$search}%");
                });
            })
            ->when(($filters['status'] ?? null) === 'approved', fn ($query) => $query->where('is_approved', true))
            ->when($request->input('status') === 'pending', fn ($query) => $query->where('is_approved', false))
            ->when(isset($filters['rating']) && $filters['rating'] !== '', fn ($query) => $query->where('rating', (int) $filters['rating']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'count' => Review::where('doctor_id', $doctor->id)->count(),
            'approved' => Review::where('doctor_id', $doctor->id)
                ->where('is_approved', true)
                ->count(),
            'pending' => Review::where('doctor_id', $doctor->id)
                ->where('is_approved', false)
                ->count(),
            'average' => round(
                Review::where('doctor_id', $doctor->id)
                    ->where('is_approved', true)
                    ->avg('rating') ?? 0,
                1
            ),
        ];

        return Inertia::render('Portal/DoctorReviews', [
            'doctor' => $doctor,
            'reviews' => $reviews,
            'stats' => $stats,
            'filters' => [
                'search' => $filters['search'] ?? null,
                'status' => $request->input('status'),
                'rating' => $filters['rating'] ?? null,
            ],
        ]);
    }
}