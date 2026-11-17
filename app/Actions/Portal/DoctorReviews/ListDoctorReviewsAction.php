<?php

declare(strict_types=1);

namespace App\Actions\Portal\DoctorReviews;

use App\Models\Doctor;
use App\Models\Review;

final class ListDoctorReviewsAction
{
    public function __construct(
        private readonly BuildDoctorReviewStatisticsAction $statisticsAction
    ) {
    }

    public function handle(array $filters, Doctor $doctor): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;
        $rating = $filters['rating'] ?? null;

        $reviews = $doctor->reviews()
            ->with('user')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('patient_name', 'like', "%{$search}%")
                        ->orWhere('comment', 'like', "%{$search}%");
                });
            })
            ->when($status === 'approved', fn ($query) => $query->where('is_approved', true))
            ->when($status === 'pending', fn ($query) => $query->where('is_approved', false))
            ->when($rating !== null && $rating !== '', fn ($query) => $query->where('rating', (int) $rating))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return [
            'reviews' => $reviews,
            'stats' => $this->statisticsAction->handle($doctor),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'rating' => $rating,
            ],
        ];
    }
}
