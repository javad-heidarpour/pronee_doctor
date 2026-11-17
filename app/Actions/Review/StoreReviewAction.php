<?php

declare(strict_types=1);

namespace App\Actions\Review;

use App\DTOs\Review\StoreReviewData;
use App\Models\Doctor;
use App\Models\Review;

final class StoreReviewAction
{
    public function handle(Doctor $doctor, StoreReviewData $data): Review
    {
        return $doctor->reviews()->create([
            'user_id' => $data->userId,
            'patient_name' => $data->patientName,
            'rating' => $data->rating,
            'comment' => $data->comment,
            'is_anonymous' => $data->isAnonymous,
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
            'doctor_reply' => null,
        ]);
    }
}
