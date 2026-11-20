<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Models\Doctor;

final class ShowDoctorProfileAction
{
    public function __construct(
        private readonly BuildPublicDoctorProfilePayloadAction $profilePayloadAction,
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
                'specialty',
                'address' => fn ($query) => $query
                    ->select([
                        'id',
                        'doctor_id',
                        'name',
                        'full_address',
                        'city_id',
                        'phone',
                        'Latitude',
                        'Longitude',
                    ])
                    ->where('is_active', true),
                'address.city',
                'socials' => fn ($query) => $query
                    ->where('is_visible', true)
                    ->whereHas('platform', fn ($platform) => $platform->where('is_active', true)),
                'socials.platform',
                'honorBadges',
            ])
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        $reviewsPage = $this->reviewsAction->handle($doctor);
        $workingHours = $this->workingHoursAction->handle($doctor);
        $bookingPage = $this->bookingAction->handle($doctor);
        $galleryPage = $this->galleryAction->handle($doctor);

        $doctorData = $this->profilePayloadAction->handle($doctor);

        return inertia('HomePage/Home1/Index', [
            'doctor' => array_merge($doctorData, [
                'canonical_url' => route('home1', ['doctor' => $doctor->slug]),
                'visitReasons' => $bookingPage['visitReasons'],
                'clinics_with_slots' => $bookingPage['clinicsWithSlots'],
                'working_hours' => $workingHours,
                'reviews' => $reviewsPage['Reviews'],
                'reviews_stats' => $reviewsPage['reviewsStats'],
                'gallery_images' => $galleryPage['galleryImages'],
                'gallery_categories' => $galleryPage['galleryCategories'],
            ]),
        ]);
    }
}
