<?php

declare(strict_types=1);

namespace App\Actions\Portal\Reviews;

use App\Models\Doctor;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class TogglePortalReviewApprovalAction
{
    public function handle(Doctor $doctor, Review $review): bool
    {
        return DB::transaction(function () use ($doctor, $review): bool {
            // Scope the locked row through the active doctor to prevent
            // cross-doctor updates even if a foreign model ID is supplied.
            $lockedReview = $doctor->reviews()
                ->whereKey($review->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $approved = ! (bool) $lockedReview->is_approved;

            $lockedReview->forceFill([
                'is_approved' => $approved,
                'approved_at' => $approved ? now() : null,
                'approved_by' => $approved ? Auth::guard('web')->id() : null,
            ])->save();

            return $approved;
        });
    }
}
