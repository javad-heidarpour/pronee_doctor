<?php

namespace App\Actions\Portal\DoctorReviews;

use App\Models\Doctor;
use App\Models\Review;

class BuildDoctorReviewStatisticsAction
{
    public function handle(Doctor $doctor): array
    {
        $reviews = Review::query()->where('doctor_id', $doctor->id);

        return [
            'count' => (clone $reviews)->count(),
            'approved' => (clone $reviews)->where('is_approved', true)->count(),
            'pending' => (clone $reviews)->where('is_approved', false)->count(),
            'average' => round(
                (clone $reviews)->where('is_approved', true)->avg('rating') ?? 0,
                1
            ),
        ];
    }
}
