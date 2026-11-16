<?php

namespace App\Actions\Portal\DoctorReviews;

use App\Http\Requests\Portal\ReplyDoctorReviewRequest;
use App\Models\Doctor;
use App\Models\Review;

class ReplyDoctorReviewAction
{
    public function handle(
        ReplyDoctorReviewRequest $request,
        Doctor $doctor,
        Review $review
    ) {
        abort_unless(
            (int) $review->doctor_id === (int) $doctor->id,
            403,
            'شما به این نظر دسترسی ندارید.'
        );

        $review->update([
            'doctor_reply' => $request->validated('doctor_reply'),
            'doctor_reply_at' => now(),
        ]);

        return back()->with('success', 'پاسخ پزشک ثبت شد.');
    }
}