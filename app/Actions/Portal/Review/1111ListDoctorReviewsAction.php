<?php

namespace App\Actions\Portal\Review;

use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;


class ListDoctorReviewsAction
{
    public function handle(Request $request)
    {
        // دریافت کاربر از گارد اصلی
        $user = Auth::guard('web')->user();

        abort_unless(
            $user,
            401,
            'کاربر وارد نشده است.'
        );

        // دریافت پروفایل پزشک
        $doctor = $user->doctorProfile;

        abort_unless(
            $doctor,
            403,
            'پروفایل پزشک برای این حساب کاربری وجود ندارد.'
        );

        $query = Review::query()

            ->where('doctor_id', $doctor->id)

            ->latest();

        /*
        |--------------------------------------------------------------------------
        | فیلتر وضعیت
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            if ($request->status === 'approved') {

                $query->where('is_approved', true);

            }

            if ($request->status === 'pending') {

                $query->where('is_approved', false);

            }

        }

        /*
        |--------------------------------------------------------------------------
        | فیلتر امتیاز
        |--------------------------------------------------------------------------
        */

        if ($request->filled('rating')) {

            $query->where('rating', $request->rating);

        }

        /*
        |--------------------------------------------------------------------------
        | جستجو
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $query->where(function ($q) use ($request) {

                $q->where(
                    'patient_name',
                    'like',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'comment',
                    'like',
                    '%' . $request->search . '%'
                )

                ->orWhere(
                    'doctor_reply',
                    'like',
                    '%' . $request->search . '%'
                );

            });

        }

        $reviews = $query

            ->paginate(10)

            ->withQueryString()

            ->through(function ($review) {

                return [

                    'id' => $review->id,

                    'patient_name' => $review->patient_display_name,

                    'rating' => $review->rating,

                    'comment' => $review->comment,

                    'doctor_reply' => $review->doctor_reply,

                    'doctor_reply_at' => optional($review->doctor_reply_at)
                        ?->format('Y-m-d H:i'),

                    'is_approved' => $review->is_approved,

                    'is_anonymous' => $review->is_anonymous,

                    'created_at' => $review->created_at
                        ->format('Y-m-d H:i'),

                    'approved_at' => optional($review->approved_at)
                        ?->format('Y-m-d H:i'),

                ];

            });

        $stats = [

            'count' => Review::where(
                'doctor_id',
                $doctor->id
            )->count(),

            'approved' => Review::where(
                'doctor_id',
                $doctor->id
            )->where(
                'is_approved',
                true
            )->count(),

            'pending' => Review::where(
                'doctor_id',
                $doctor->id
            )->where(
                'is_approved',
                false
            )->count(),

            'average' => round(

                Review::where(
                    'doctor_id',
                    $doctor->id
                )

                ->where(
                    'is_approved',
                    true
                )

                ->avg('rating'),

                1

            ),

        ];

        return Inertia::render(

            'Portal/DoctorReviews',

            [

                'reviews' => $reviews,

                'stats' => $stats,

                'filters' => [

                    'status' => $request->status,

                    'rating' => $request->rating,

                    'search' => $request->search,

                ],

            ]

        );
    }
}