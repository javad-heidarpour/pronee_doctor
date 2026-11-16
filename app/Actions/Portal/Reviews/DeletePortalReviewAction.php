<?php

namespace App\Actions\Portal\Reviews;

use App\Models\Doctor;
use App\Models\Review;

class DeletePortalReviewAction
{
    public function handle(Doctor $doctor, Review $review): void
    {
        abort_unless(
            (int) $review->doctor_id === (int) $doctor->id,
            403,
            'شما به این نظر دسترسی ندارید.'
        );

        $review->delete();
    }
}
