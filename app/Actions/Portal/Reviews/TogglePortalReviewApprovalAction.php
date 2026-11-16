<?php

namespace App\Actions\Portal\Reviews;

use App\Models\Doctor;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;

class TogglePortalReviewApprovalAction
{
    public function handle(Doctor $doctor, Review $review): bool
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
            'approved_by' => $approved ? Auth::guard('web')->id() : null,
        ]);

        return $approved;
    }
}
