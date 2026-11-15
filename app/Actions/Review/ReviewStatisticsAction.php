<?php

namespace App\Actions\Review;

use App\Models\Doctor;

class ReviewStatisticsAction
{
    public function handle(
        Doctor $doctor
    ): array {

        $reviews = $doctor
            ->approvedReviews();

        return [

            'count' => $reviews->count(),

            'average' => round(
                $reviews->avg('rating') ?? 0,
                1
            ),

            'distribution' => [

                5 => (clone $reviews)->where('rating', 5)->count(),

                4 => (clone $reviews)->where('rating', 4)->count(),

                3 => (clone $reviews)->where('rating', 3)->count(),

                2 => (clone $reviews)->where('rating', 2)->count(),

                1 => (clone $reviews)->where('rating', 1)->count(),

            ],

        ];
    }
}