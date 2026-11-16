<?php

namespace App\Actions\Portal\DoctorReviews;

use App\Models\Doctor;
use App\Models\Review;

class ReplyDoctorReviewAction
{
    public function handle(
        Doctor $doctor,
        Review $review,
        string $doctorReply
    ) {
        abort_unless(
            (int) $review->doctor_id === (int) $doctor->id,
            403,
            'شما به این نظر دسترسی ندارید.'
        );

        $review->update([
            'doctor_reply' => $doctorReply,
            'doctor_reply_at' => now(),
        ]);

        return back()->with('success', 'پاسخ پزشک ثبت شد.');
    }
}