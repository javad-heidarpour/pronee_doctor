<?php

namespace App\Actions\Review;

use App\Models\Review;

class ApproveReviewAction
{
    public function handle(Review $review)
    {
        $review->update([

            'is_approved' => true,

            'approved_at' => now(),

            'approved_by' => auth()->id(),

        ]);

        return back()->with(
            'success',
            'نظر با موفقیت تایید شد.'
        );
    }
}