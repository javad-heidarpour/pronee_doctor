<?php

namespace App\\Actions\\Home;

use App\\Models\\Doctor;
use Morilog\\Jalali\\Jalalian;

class BuildPublicDoctorReviewsPageAction
{
    /**
     * Build the public reviews payload for a doctor.
     *
     * @return array{Reviews: \\Illuminate\\Support\\Collection, reviewsStats: array}
     */
    public function handle(Doctor $doctor): array
    {
        $reviews = $doctor->approvedReviews()
            ->with('doctor')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'doctor_reply' => $review->doctor_reply,
                    'patient_name' => $review->patient_display_name,
                    'doctor_name' => trim(
                        $review->doctor->caption . ' ' .
                        $review->doctor->name .
                        $review->doctor->family
                    ),
                    'rating' => (int) $review->rating,
                    'comment' => $review->comment,
                    'is_anonymous' => $review->is_anonymous,
                    'persian_date' => Jalalian::fromDateTime(
                        $review->created_at
                    )->format('%d %B %Y'),
                ];
            });

        $approvedReviews = $doctor->approvedReviews();

        $reviewsStats = [
            'average' => round($approvedReviews->avg('rating') ?? 0, 1),
            'count' => $approvedReviews->count(),
            'distribution' => [
                5 => $doctor->approvedReviews()->where('rating', 5)->count(),
                4 => $doctor->approvedReviews()->where('rating', 4)->count(),
                3 => $doctor->approvedReviews()->where('rating', 3)->count(),
                2 => $doctor->approvedReviews()->where('rating', 2)->count(),
                1 => $doctor->approvedReviews()->where('rating', 1)->count(),
            ],
        ];

        return [
            'Reviews' => $reviews,
            'reviewsStats' => $reviewsStats,
        ];
    }
}
