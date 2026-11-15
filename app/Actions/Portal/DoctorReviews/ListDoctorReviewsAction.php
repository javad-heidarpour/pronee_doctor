<?php

namespace App\Actions\Portal\DoctorReviews;

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


        abort_if(
            !$doctor,
            401,
            'پزشک وارد نشده است'
        );



        abort_if(
            !$doctor,
            403,
            'پروفایل پزشک برای این کاربر وجود ندارد'
        );


        $reviews = Review::query()

            ->where('doctor_id', $doctor->id)

            ->when(
                $request->search,
                function ($query) use ($request) {

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
                            );

                    });

                }
            )

            ->when(
                $request->status === 'approved',
                function ($query) {

                    $query->where(
                        'is_approved',
                        true
                    );

                }
            )

            ->when(
                $request->status === 'pending',
                function ($query) {

                    $query->where(
                        'is_approved',
                        false
                    );

                }
            )

            ->when(
                $request->rating,
                function ($query) use ($request) {

                    $query->where(
                        'rating',
                        $request->rating
                    );

                }
            )

            ->latest()

            ->paginate(10)

            ->withQueryString();



        $stats = [

            'count' => Review::where(
                'doctor_id',
                $doctor->id
            )->count(),


            'approved' => Review::where(
                'doctor_id',
                $doctor->id
            )
                ->where(
                    'is_approved',
                    true
                )
                ->count(),


            'pending' => Review::where(
                'doctor_id',
                $doctor->id
            )
                ->where(
                    'is_approved',
                    false
                )
                ->count(),


            'average' => round(
                Review::where(
                    'doctor_id',
                    $doctor->id
                )
                    ->where(
                        'is_approved',
                        true
                    )
                    ->avg('rating') ?? 0,
                1
            ),

        ];


    $user = Auth::guard('web')->user();
        $doctor = $user->doctorProfile;
        return Inertia::render(
            'Portal/DoctorReviews',
            [

                'doctor' => $doctor,
                'reviews' => $reviews,

                'stats' => $stats,

                'filters' => [

                    'search' => $request->search,

                    'status' => $request->status,

                    'rating' => $request->rating,

                ],

            ]
        );
    }
}