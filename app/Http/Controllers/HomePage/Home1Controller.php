<?php

namespace App\\Http\\Controllers\\HomePage;

use App\\Actions\\Home\\BuildPublicDoctorBookingPageAction;
use App\\Actions\\Home\\BuildPublicDoctorGalleryPageAction;
use App\\Actions\\Home\\BuildPublicDoctorReviewsPageAction;
use App\\Actions\\Home\\BuildPublicWorkingHoursAction;
use App\\Actions\\Home\\FindPublicDoctorBySlugAction;
use App\\Actions\\Home\\ListPublicMedicalServicesAction;
use App\\Actions\\Home\\ListPublicSurgicalProceduresAction;
use App\\Http\\Controllers\\Controller;
use App\\Models\\Doctor;
use Inertia\\Inertia;

class Home1Controller extends Controller
{
    /**
     * Resolve the public doctor context through a dedicated query action.
     */
    private function findDoctor(
        string $slug,
        FindPublicDoctorBySlugAction $action
    ): Doctor {
        return $action->handle($slug);
    }

    public function WorkingHours(
        string $slug,
        BuildPublicWorkingHoursAction $action,
        FindPublicDoctorBySlugAction $doctorAction
    ) {
        $doctor = $this->findDoctor($slug, $doctorAction);

        return Inertia::render('HomePage/Home1/WorkingHours', [
            'doctor' => $doctor,
            'working_hours' => $action->handle($doctor),
        ]);
    }

    public function About(
        string $slug,
        FindPublicDoctorBySlugAction $doctorAction
    ) {
        $doctor = $this->findDoctor($slug, $doctorAction);

        return Inertia::render('HomePage/Home1/About', [
            'doctor' => $doctor,
        ]);
    }

    public function Reviews(
        string $slug,
        BuildPublicDoctorReviewsPageAction $action,
        FindPublicDoctorBySlugAction $doctorAction
    ) {
        $doctor = $this->findDoctor($slug, $doctorAction);
        $reviewsPage = $action->handle($doctor);

        return Inertia::render('HomePage/Home1/Reviews', [
            'doctor' => $doctor,
            'Reviews' => $reviewsPage['Reviews'],
            'reviewsStats' => $reviewsPage['reviewsStats'],
        ]);
    }

    public function tips(
        string $slug,
        FindPublicDoctorBySlugAction $doctorAction
    ) {
        $doctor = $this->findDoctor($slug, $doctorAction);

        return Inertia::render('HomePage/Home1/tips', [
            'doctor' => $doctor,
        ]);
    }

    public function galleries(
        string $slug,
        BuildPublicDoctorGalleryPageAction $action,
        FindPublicDoctorBySlugAction $doctorAction
    ) {
        $doctor = $this->findDoctor($slug, $doctorAction);
        $galleryPage = $action->handle($doctor);

        return Inertia::render('HomePage/Home1/galleries', [
            'doctor' => $doctor,
            'galleryCategories' => $galleryPage['galleryCategories'],
            'galleryImages' => $galleryPage['galleryImages'],
        ]);
    }

    public function contact(
        string $slug,
        FindPublicDoctorBySlugAction $doctorAction
    ) {
        $doctor = $this->findDoctor($slug, $doctorAction);

        return Inertia::render('HomePage/Home1/contact', [
            'doctor' => $doctor,
        ]);
    }

    public function Surgeries(
        string $slug,
        ListPublicSurgicalProceduresAction $action,
        FindPublicDoctorBySlugAction $doctorAction
    ) {
        $doctor = $this->findDoctor($slug, $doctorAction);
        $procedures = $action->handle();

        return Inertia::render('HomePage/Home1/Surgeries', [
            'doctor' => $doctor,
            'procedures' => $procedures,
        ]);
    }

    public function services(
        string $slug,
        ListPublicMedicalServicesAction $action,
        FindPublicDoctorBySlugAction $doctorAction
    ) {
        $doctor = $this->findDoctor($slug, $doctorAction);
        $services = $action->handle();

        return Inertia::render('HomePage/Home1/Services', [
            'doctor' => $doctor,
            'services' => $services,
        ]);
    }

    public function booking(
        string $slug,
        BuildPublicDoctorBookingPageAction $action,
        FindPublicDoctorBySlugAction $doctorAction,
        $clinic = null
    ) {
        $doctor = $this->findDoctor($slug, $doctorAction);
        $bookingPage = $action->handle($doctor);
        $clinicsWithSlots = $bookingPage['clinicsWithSlots'];

        $doctor->setAttribute('clinics_with_slots', $clinicsWithSlots);

        return Inertia::render('HomePage/Home1/Booking', [
            'doctor' => $doctor,
            'clinicsWithSlots' => $clinicsWithSlots,
            'selectedClinic' => $clinic,
            'visitReasons' => $bookingPage['visitReasons'],
        ]);
    }
}
