<?php

declare(strict_types=1);

namespace App\Actions\Portal\DoctorReviews;

use App\Models\Doctor;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final class ReplyDoctorReviewAction
{
    public function handle(
        Doctor $doctor,
        Review $review,
        string $doctorReply
    ): RedirectResponse {
        DB::transaction(function () use ($doctor, $review, $doctorReply): void {
            $lockedReview = $doctor->reviews()
                ->whereKey($review->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedReview->forceFill([
                'doctor_reply' => $doctorReply,
                'doctor_reply_at' => now(),
            ])->save();
        });

        return back()->with('success', 'پاسخ پزشک ثبت شد.');
    }
}
