<?php

namespace App\Http\Controllers\HomePage;

use App\Http\Controllers\Controller;
use App\Actions\Home\BuildPublicWorkingHoursAction;
use App\Actions\Home\BuildPublicDoctorReviewsPageAction;
use App\Actions\Home\BuildPublicDoctorGalleryPageAction;
use App\Actions\Home\ListPublicMedicalServicesAction;
use App\Actions\Home\ListPublicSurgicalProceduresAction;
use Morilog\Jalali\Jalalian;
use Inertia\Inertia;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\VisitReason;

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

    public function booking(string $slug, $clinic = null)
    {
        $doctor = $this->findDoctor($slug);
        // این قسمت را با روش فعلی دریافت پزشک جایگزین کن.

        $today = now()->toDateString();
        $oneHourLater = now()->addHour();
        $visitReasons = VisitReason::query()
            ->select('id', 'title')
            ->orderBy('title')
            ->get();
        $clinicsWithSlots = $doctor->address

            ->map(function ($clinic) use ($doctor, $today, $oneHourLater) {

                $slots = $doctor->timeSlots()

                    ->where('clinic_id', $clinic->id)

                    ->whereIn('status', ['available', 'booked'])

                    ->where(function ($query) use ($today, $oneHourLater) {

                        $query
                            ->where('date', '>', $today)

                            ->orWhere(function ($q) use ($today, $oneHourLater) {

                                $q
                                    ->where('date', $today)
                                    ->where(
                                        'start_time',
                                        '>=',
                                        $oneHourLater->format('H:i:s')
                                    );
                            });
                    })

                    ->orderBy('date')
                    ->orderBy('start_time')
                    ->get();

                return [
                    'clinic' => $clinic,

                    'dates' => $slots

                        ->groupBy('date')

                        ->map(function ($daySlots) {

                            return [
                                'date' => $daySlots->first()->date->format('Y-m-d'),

                                'slots' => $daySlots->map(function ($slot) {

                                    $booked = Appointment::where(
                                        'time_slot_id',
                                        $slot->id
                                    )
                                        ->whereIn('status', [
                                            'pending',
                                            'confirmed'
                                        ])
                                        ->exists();

                                    return [
                                        'id' => $slot->id,
                                        'start_time' => substr($slot->start_time, 0, 5),
                                        'status' => $booked ? 'booked' : 'available',
                                    ];
                                })->values(),
                            ];
                        })
                        ->values(),
                ];
            })

            ->filter(function ($clinic) {
                return $clinic['dates']->isNotEmpty();
            })
            ->values();
        $doctor->setAttribute(
            'clinics_with_slots',
            $clinicsWithSlots
        );
        return Inertia::render('HomePage/Home1/Booking', [
            'doctor' => $doctor,
            'clinicsWithSlots' => $clinicsWithSlots,
            'selectedClinic' => $clinic,
            'visitReasons' => $visitReasons,

        ]);
    }
}