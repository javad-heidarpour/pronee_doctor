<?php

namespace App\Actions\Review;

use App\Models\Review;

class DeleteReviewAction
{
    public function handle(
        Review $review
    ) {

        $review->delete();

        return back()->with(
            'success',
            'نظر حذف شد.'
        );
    }
}