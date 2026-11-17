<?php

declare(strict_types=1);

namespace App\Actions\Portal\Reviews;

use App\Models\Doctor;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

final class DeletePortalReviewAction
{
    public function handle(Doctor $doctor, Review $review): void
    {
        DB::transaction(function () use ($doctor, $review): void {
            $lockedReview = $doctor->reviews()
                ->whereKey($review->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedReview->delete();
        });
    }
}
