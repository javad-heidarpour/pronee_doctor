<?php

namespace App\Actions\Home;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorImage;
use App\Models\VisitReason;
use App\Models\DoctorWorkingHour;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class ShowDoctorProfileAction
{
    /**
     * نمایش صفحه اصلی فعلی
     */
    public function handle(string $slug)
    {
        return $this->renderDoctorPage($slug, 'HomePage');
    }

    /**
     * نمایش قالب جدید Home1
     */
    public function handleHome1(string $slug)
    {
        return $this->renderDoctorPage($slug, 'HomePage/Home1/Index');
    }

    /**
     * منطق مشترک دریافت اطلاعات پزشک و نمایش قالب
     */
    private function renderDoctorPage(string $slug, string $component)
    {
        /*
        |--------------------------------------------------------------------------
        | Doctor
        |--------------------------------------------------------------------------
        */

        $doctor = Doctor::with([
            'city',
            'reviews',
            'specialty',
            'user',
            'address',
            'address.city',
            'timeSlots',
            'socials.platform'
        ])
            ->where('slug', $slug)
            ->firstOrFail();





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


        /*
        |--------------------------------------------------------------------------
        | Working Hours
        |--------------------------------------------------------------------------
        */

        $workingHours = DoctorWorkingHour::with('clinic')
            ->where('doctor_id', $doctor->id)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy(function ($hour) {
                return $hour->day_of_week . '_' . $hour->clinic_id;
            })
            ->map(function ($hours) {

                $firstHour = $hours->first();

                return [
                    'day_of_week' => $firstHour->day_of_week,
                    'day_name' => $firstHour->day_name,
                    'clinic_id' => $firstHour->clinic_id,
                    'clinic' => $firstHour->clinic?->name,

                    'hours' => $hours
                        ->unique(function ($item) {
                            return $item->start_time . '_' . $item->end_time;
                        })
                        ->map(function ($hour) {
                            return $hour->formatted_start_time
                                . ' - ' .
                                $hour->formatted_end_time;
                        })
                        ->implode(' / '),
                ];
            })
            ->sortBy('day_of_week')
            ->values();


        $allDays = collect([
            ['day_of_week' => 0, 'day_name' => 'شنبه'],
            ['day_of_week' => 1, 'day_name' => 'یکشنبه'],
            ['day_of_week' => 2, 'day_name' => 'دوشنبه'],
            ['day_of_week' => 3, 'day_name' => 'سه‌شنبه'],
            ['day_of_week' => 4, 'day_name' => 'چهارشنبه'],
            ['day_of_week' => 5, 'day_name' => 'پنجشنبه'],
            ['day_of_week' => 6, 'day_name' => 'جمعه'],
        ]);


        $finalWorkingHours = $allDays
            ->map(function ($day) use ($workingHours) {

                $exist = $workingHours->firstWhere(
                    'day_of_week',
                    $day['day_of_week']
                );

                return [
                    'name' => $day['day_name'],

                    'hours' => $exist
                        ? $exist['hours']
                        : null,

                    'clinic' => $exist
                        ? $exist['clinic']
                        : null,
                ];
            });


        /*
        |--------------------------------------------------------------------------
        | Appointment Slots
        |--------------------------------------------------------------------------
        */

        $now = Carbon::now();

        $today = $now->toDateString();

        $oneHourLater = $now->copy()->addHour();


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
                                'date' => $daySlots
                                    ->first()
                                    ->date
                                    ->format('Y-m-d'),

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

                                        'start_time' => substr(
                                            $slot->start_time,
                                            0,
                                            5
                                        ),

                                        'status' => $booked
                                            ? 'booked'
                                            : 'available',
                                    ];
                                }),
                            ];
                        })
                        ->values(),
                ];
            })

            ->filter(function ($clinic) {
                return $clinic['dates']->isNotEmpty();
            })

            ->values();


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


        /*
        |--------------------------------------------------------------------------
        | Visit Reasons
        |--------------------------------------------------------------------------
        */

        $visitReasons = VisitReason::all();


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return inertia($component, [

            'doctor' => array_merge(
                $doctor->toArray(),
                [
                    'visitReasons' => $visitReasons,
                    'clinics_with_slots' => $clinicsWithSlots,
                    'working_hours' => $finalWorkingHours,
                    'reviews' => $reviews,
                    'reviews_stats' => $reviewsStats,
                    'gallery_images' => $galleryImages,
                    'gallery_categories' => $galleryCategories,
                ]
            ),

        ]);
    }
}
