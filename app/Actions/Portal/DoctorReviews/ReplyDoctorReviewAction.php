<?php

declare(strict_types=1);

namespace App\Actions\Portal\DoctorReviews;

use App\Models\Doctor;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

final class ReplyDoctorReviewAction
{
    public function handle(
        Doctor $doctor,
        Review $review,
        string $doctorReply
    ): Review {
        return DB::transaction(function () use ($doctor, $review, $doctorReply): Review {
            $lockedReview = $doctor->reviews()
                ->whereKey($review->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedReview->forceFill([
                'doctor_reply' => $doctorReply,
                'doctor_reply_at' => now(),
            ])->save();

            return $lockedReview->refresh();
        });
    }
}
