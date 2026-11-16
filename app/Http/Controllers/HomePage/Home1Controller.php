<?php

namespace App\Http\Controllers\HomePage;

use App\Http\Controllers\Controller;
use App\Actions\Home\BuildPublicWorkingHoursAction;
use Illuminate\Http\Request;
use App\Actions\Home\HomeIndexAction;
use App\Actions\Home\ShowDoctorProfileAction;
use App\Actions\Home\BookAppointmentAction;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use Inertia\Inertia;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\VisitReason;
use App\Models\MedicalService;
use App\Models\SurgicalProcedure;
use App\Models\DoctorImage;
use App\Models\DoctorWorkingHour;

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

    public function Reviews(string $slug)
    {

        $doctor = $this->findDoctor($slug);
        // این قسمت را با روش فعلی دریافت پزشک جایگزین کن.

        /*
        |--------------------------------------------------------------------------
        | Reviews
        |--------------------------------------------------------------------------
        */

        $reviews = $doctor->approvedReviews()
            ->with('doctor')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($review) {

                return [
                    'id' => $review->id,
                    'doctor_reply' => $review->doctor_reply,
                    'patient_name' => $review->patient_display_name,

                    'doctor_name' => trim(
                        $review->doctor->caption . ' ' .
                        $review->doctor->name .
                        $review->doctor->family
                    ),

                    'rating' => (int) $review->rating,
                    'comment' => $review->comment,
                    'is_anonymous' => $review->is_anonymous,

                    'persian_date' => Jalalian::fromDateTime(
                        $review->created_at
                    )->format('%d %B %Y'),
                ];
            });
        // dd( $reviews);
        $approvedReviews = $doctor->approvedReviews();

        $reviewsStats = [

            'average' => round(
                $approvedReviews->avg('rating') ?? 0,
                1
            ),

            'count' => $approvedReviews->count(),

            'distribution' => [
                5 => $doctor->approvedReviews()->where('rating', 5)->count(),
                4 => $doctor->approvedReviews()->where('rating', 4)->count(),
                3 => $doctor->approvedReviews()->where('rating', 3)->count(),
                2 => $doctor->approvedReviews()->where('rating', 2)->count(),
                1 => $doctor->approvedReviews()->where('rating', 1)->count(),
            ],
        ];
        // dd( $reviewsStats);

        return Inertia::render('HomePage/Home1/Reviews', [
            'doctor' => $doctor,
            'Reviews' => $reviews,
            'reviewsStats' => $reviewsStats,

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
    public function galleries(string $slug)
    {
        $doctor = $this->findDoctor($slug);

        /*
        |--------------------------------------------------------------------------
        | Gallery
        |--------------------------------------------------------------------------
        */

        $galleryImages = $doctor->activeImages()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($image) {

                return [
                    'id' => $image->id,
                    'image_url' => $image->full_image_url,
                    'caption' => $image->caption,
                    'category' => $image->category,
                    'category_name' => $image->category_name,
                    'sort_order' => $image->sort_order,
                ];
            });


        $galleryCategories = $doctor->activeImages()
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->get()
            ->map(function ($item) {

                return [
                    'value' => $item->category,

                    'label' => DoctorImage::CATEGORIES[$item->category] ?? 'سایر',

                    'count' => $item->count,
                ];
            })
            ->sortBy('label')
            ->values();


        return Inertia::render('HomePage/Home1/galleries', [
            'doctor' => $doctor,
            'galleryCategories' => $galleryCategories,
            'galleryImages' => $galleryImages,


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


    public function Surgeries(string $slug)
    {
        $doctor = $this->findDoctor($slug);
        $procedures = SurgicalProcedure::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
        // dd(   $doctor);

        return Inertia::render('HomePage/Home1/Surgeries', [
            'doctor' => $doctor,
            'procedures' => $procedures,
        ]);
    }
    public function services(string $slug)
    {
        $doctor = $this->findDoctor($slug);

        $services = MedicalService::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

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