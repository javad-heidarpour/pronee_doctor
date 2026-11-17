<?php

declare(strict_types=1);

namespace App\Actions\Portal\DoctorReviews;

use App\Models\Doctor;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class ToggleDoctorReviewAction
{
    public function handle(Doctor $doctor, Review $review): RedirectResponse
    {
        $approved = DB::transaction(function () use ($doctor, $review): bool {
            $lockedReview = $doctor->reviews()
                ->whereKey($review->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $nextState = ! (bool) $lockedReview->is_approved;

            $lockedReview->forceFill([
                'is_approved' => $nextState,
                'approved_at' => $nextState ? now() : null,
                'approved_by' => $nextState ? Auth::guard('web')->id() : null,
            ])->save();

            return $nextState;
        });

        return back()->with(
            'success',
            $approved ? 'نظر تأیید شد.' : 'نظر از حالت تأیید خارج شد.'
        );
    }
}
