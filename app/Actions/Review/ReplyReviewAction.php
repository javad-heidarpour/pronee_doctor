<?php

namespace App\Actions\Review;

use App\Models\Review;
use Illuminate\Http\Request;

class ReplyReviewAction
{
    public function handle(
        Request $request,
        Review $review
    ) {

        $data = $request->validate([

            'doctor_reply' => [
                'required',
                'string',
                'min:3',
                'max:5000',
            ],

        ]);

        $review->update([

            'doctor_reply' => $data['doctor_reply'],

        ]);

        return back()->with(
            'success',
            'پاسخ پزشک ثبت شد.'
        );
    }
}