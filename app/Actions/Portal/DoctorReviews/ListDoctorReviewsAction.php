<?php

namespace App\Actions\Portal\DoctorReviews;

use App\Models\Doctor;
use App\Models\Review;
use Inertia\Inertia;

class ListDoctorReviewsAction
{
    public function handle(array $filters, Doctor $doctor)
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;
        $rating = $filters['rating'] ?? null;

        $reviews = Review::query()
            ->where('doctor_id', $doctor->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('patient_name', 'like', "%{$search}%")
                        ->orWhere('comment', 'like', "%{$search}%");
                });
            })
            ->when($status === 'approved', fn ($query) => $query->where('is_approved', true))
            ->when($status === 'pending', fn ($query) => $query->where('is_approved', false))
            ->when($rating !== null && $rating !== '', fn ($query) => $query->where('rating', (int) $rating))
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
                'search' => $search,
                'status' => $status,
                'rating' => $rating,
            ],
        ]);
    }
}
