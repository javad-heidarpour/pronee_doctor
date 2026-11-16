<?php

namespace App\Actions\Portal\Reviews;

use App\Models\Doctor;
use App\Models\Review;
use Illuminate\Support\Collection;

class ListPortalReviewsAction
{
    public function handle(Doctor $doctor, string $filter = 'all'): Collection
    {
        $query = $doctor->reviews()->with('user');

        if ($filter === 'approved') {
            $query->where('is_approved', true);
        } elseif ($filter === 'pending') {
            $query->where('is_approved', false);
        }

        return $query->latest()->get()->map(fn (Review $review) => [
            'id' => $review->id,
            'patient_name' => $review->patient_display_name,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'is_approved' => $review->is_approved,
            'persian_date' => $review->persian_date,
        ]);
    }
}
