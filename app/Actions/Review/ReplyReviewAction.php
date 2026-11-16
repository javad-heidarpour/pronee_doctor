<?php

namespace App\Actions\Review;

use App\Models\Review;

class ReplyReviewAction
{
    public function handle(Review $review, string $doctorReply)
    {
        $review->update([
            'doctor_reply' => $doctorReply,
        ]);

        return back()->with('success', 'پاسخ پزشک ثبت شد.');
    }
}
