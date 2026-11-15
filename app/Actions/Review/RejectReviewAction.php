<?php

namespace App\Actions\Review;

use App\Models\Review;

class RejectReviewAction
{
    public function handle(Review $review)
    {
        $review->update([

            'is_approved' => false,

            'approved_at' => null,

            'approved_by' => null,

        ]);

        return back()->with(
            'success',
            'نظر رد شد.'
        );
    }
}