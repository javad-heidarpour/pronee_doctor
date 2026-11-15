<?php

namespace App\Actions\Portal\DoctorReviews;

use App\Models\Review;
use Illuminate\Support\Facades\Auth;

class ToggleDoctorReviewAction
{
    public function handle(Review $review)
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
            401
        );


        abort_if(
            !$doctor,
            403
        );

        abort_if(
            $review->doctor_id !== $doctor->id,
            403
        );


        $review->update([

            'is_approved' => !$review->is_approved,

            'approved_at' => !$review->is_approved
                ? now()
                : null,

            'approved_by' => !$review->is_approved
                ? Auth::id()
                : null,

        ]);


        return back()->with(
            'success',
            'وضعیت نظر با موفقیت تغییر کرد.'
        );
    }
}