<?php

namespace App\Actions\Portal\Review;

use App\Http\Requests\Portal\ReplyReviewRequest;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;


class ReplyDoctorReviewAction
{
    public function handle(
        ReplyReviewRequest $request,
        Review $review
    ) {
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

        abort_unless(
            $review->doctor_id === $doctor->id,
            403
        );

        $review->update([

            'doctor_reply' => $request->doctor_reply,

            'doctor_reply_at' => now(),

        ]);

        return back()->with(
            'success',
            'پاسخ شما با موفقیت ثبت شد.'
        );
    }
}