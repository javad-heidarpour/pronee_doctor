<?php

namespace App\Actions\Portal\DoctorReviews;

use App\Models\Doctor;
use App\Models\Review;

class ToggleDoctorReviewAction
{
    public function handle(Doctor $doctor, Review $review)
    {
        abort_unless(
            (int) $review->doctor_id === (int) $doctor->id,
            403,
            'شما به این نظر دسترسی ندارید.'
        );

        $approved = !$review->is_approved;

        $review->update([
            'is_approved' => $approved,
            'approved_at' => $approved ? now() : null,
            'approved_by' => $approved ? auth('web')->id() : null,
        ]);

        return back()->with(
            'success',
            $approved ? 'نظر تأیید شد.' : 'نظر از حالت تأیید خارج شد.'
        );
    }
}