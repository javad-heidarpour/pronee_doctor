<?php

namespace App\Actions\Review;

use App\Models\Doctor;

class LoadDoctorReviewsAction
{
    public function handle(
        Doctor $doctor
    ) {

        return $doctor->approvedReviews()
            ->latest()
            ->get()
            ->map(function ($review) {

                return [

                    'id' => $review->id,

                    'patient_name' => $review->patient_display_name,

                    'rating' => $review->rating,

                    'comment' => $review->comment,

                    'doctor_reply' => $review->doctor_reply,

                    'persian_date' => $review->persian_date,

                    'created_at' => $review->created_at,

                ];

            });

    }
}