<?php

namespace App\Actions\Home;

use App\Models\Doctor;
use App\Models\VisitReason;

class ShowDoctorProfileAction
{
    public function __construct(
        private readonly BuildPublicDoctorReviewsPageAction $reviewsAction,
        private readonly BuildPublicWorkingHoursAction $workingHoursAction,
        private readonly BuildPublicDoctorBookingPageAction $bookingAction,
        private readonly BuildPublicDoctorGalleryPageAction $galleryAction,
    ) {
    }

    public function handle(string $slug)
    {
        $doctor = Doctor::query()
            ->with([
                'city',
                'reviews',
                'specialty',
                'user',
                'address' => fn ($query) => $query->where('is_active', true),
                'address.city',
                'timeSlots',
                'socials.platform',
            ])
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        $reviewsPage = $this->reviewsAction->handle($doctor);
        $workingHours = $this->workingHoursAction->handle($doctor);
        $bookingPage = $this->bookingAction->handle($doctor);
        $galleryPage = $this->galleryAction->handle($doctor);

        return inertia('HomePage/Home1/Index', [
            'doctor' => array_merge(
                $doctor->toArray(),
                [
                    'visitReasons' => VisitReason::all(),
                    'clinics_with_slots' => $bookingPage['clinicsWithSlots'],
                    'working_hours' => $workingHours,
                    'reviews' => $reviewsPage['Reviews'],
                    'reviews_stats' => $reviewsPage['reviewsStats'],
                    'gallery_images' => $galleryPage['galleryImages'],
                    'gallery_categories' => $galleryPage['galleryCategories'],
                ]
            ),
        ]);
    }
}
