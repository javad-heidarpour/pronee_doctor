<?php

namespace App\Http\Controllers\HomePage;

use App\Http\Controllers\Controller;
use App\Actions\Home\BuildPublicWorkingHoursAction;
use App\Actions\Home\BuildPublicDoctorReviewsPageAction;
use App\Actions\Home\BuildPublicDoctorGalleryPageAction;
use App\Actions\Home\ListPublicMedicalServicesAction;
use App\Actions\Home\ListPublicSurgicalProceduresAction;
use App\Actions\Home\BuildPublicDoctorBookingPageAction;
use Morilog\Jalali\Jalalian;
use Inertia\Inertia;
use App\Models\Doctor;

class Home1Controller extends Controller
{
    /**
     * Public pages are scoped to the doctor identified by the route slug.
     * Never fall back to a fixed doctor ID.
     */
    private function findDoctor(string $slug): Doctor
    {
        return Doctor::query()
            ->with([
                'city',
                'reviews',
                'specialty',
                'user',
                'address',
                'address.city',
                'timeSlots',
                'socials.platform',
            ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function WorkingHours(
        string $slug,
        BuildPublicWorkingHoursAction $action
    ) {
        $doctor = $this->findDoctor($slug);

        return Inertia::render('HomePage/Home1/WorkingHours', [
            'doctor' => $doctor,
            'working_hours' => $action->handle($doctor),
        ]);
    }

    public function About(string $slug)
    {
        $doctor = $this->findDoctor($slug);

        return Inertia::render('HomePage/Home1/About', [
            'doctor' => $doctor,
            // 'services' => $services,
        ]);
    }

    public function Reviews(
        string $slug,
        BuildPublicDoctorReviewsPageAction $action
    ) {
        $doctor = $this->findDoctor($slug);
        $reviewsPage = $action->handle($doctor);

        return Inertia::render('HomePage/Home1/Reviews', [
            'doctor' => $doctor,
            'Reviews' => $reviewsPage['Reviews'],
            'reviewsStats' => $reviewsPage['reviewsStats'],
        ]);
    }

    public function tips(string $slug)
    {
        $doctor = $this->findDoctor($slug);
        // این قسمت را با روش فعلی دریافت پزشک جایگزین کن.

        return Inertia::render('HomePage/Home1/tips', [
            'doctor' => $doctor,

        ]);
    }
    public function galleries(
        string $slug,
        BuildPublicDoctorGalleryPageAction $action
    ) {
        $doctor = $this->findDoctor($slug);
        $galleryPage = $action->handle($doctor);

        return Inertia::render('HomePage/Home1/galleries', [
            'doctor' => $doctor,
            'galleryCategories' => $galleryPage['galleryCategories'],
            'galleryImages' => $galleryPage['galleryImages'],
        ]);
    }

    public function contact(string $slug)
    {
        $doctor = $this->findDoctor($slug);
        // این قسمت را با روش فعلی دریافت پزشک جایگزین کن.


        return Inertia::render('HomePage/Home1/contact', [
            'doctor' => $doctor,

        ]);
    }


    public function Surgeries(
        string $slug,
        ListPublicSurgicalProceduresAction $action
    ) {
        $doctor = $this->findDoctor($slug);
        $procedures = $action->handle();

        return Inertia::render('HomePage/Home1/Surgeries', [
            'doctor' => $doctor,
            'procedures' => $procedures,
        ]);
    }

    public function services(
        string $slug,
        ListPublicMedicalServicesAction $action
    ) {
        $doctor = $this->findDoctor($slug);
        $services = $action->handle();

        return Inertia::render('HomePage/Home1/Services', [
            'doctor' => $doctor,
            'services' => $services,
        ]);
    }

    public function booking(
        string $slug,
        BuildPublicDoctorBookingPageAction $action,
        $clinic = null
    ) {
        $doctor = $this->findDoctor($slug);
        $bookingPage = $action->handle($doctor);
        $clinicsWithSlots = $bookingPage['clinicsWithSlots'];

        $doctor->setAttribute(
            'clinics_with_slots',
            $clinicsWithSlots
        );

        return Inertia::render('HomePage/Home1/Booking', [
            'doctor' => $doctor,
            'clinicsWithSlots' => $clinicsWithSlots,
            'selectedClinic' => $clinic,
            'visitReasons' => $bookingPage['visitReasons'],
        ]);
    }
}
