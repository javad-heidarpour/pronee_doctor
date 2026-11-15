<?php

namespace App\Actions\Review;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Doctor;
use App\Models\Review;

class StoreReviewAction
{
    public function handle(
        StoreReviewRequest $request,
        Doctor $doctor
    ) {

        $data = $request->validated();

        Review::create([

            'doctor_id'      => $doctor->id,

            'user_id'        => auth()->id(),

            'patient_name'   => $data['patient_name'],

            'rating'         => $data['rating'],

            'comment'        => $data['comment'],

            'is_anonymous'   => $data['is_anonymous'] ?? false,

            'is_approved'    => false,

            'approved_at'    => null,

            'approved_by'    => null,

            'doctor_reply'   => null,

        ]);

        return back()->with(

            'success',

            'نظر شما با موفقیت ثبت شد و پس از تایید پزشک نمایش داده خواهد شد.'

        );
    }
}