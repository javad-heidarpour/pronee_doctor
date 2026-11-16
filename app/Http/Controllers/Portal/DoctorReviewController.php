<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\DoctorReviews\ListDoctorReviewsAction;
use App\Actions\Portal\DoctorReviews\ReplyDoctorReviewAction;
use App\Actions\Portal\DoctorReviews\ToggleDoctorReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Http\Requests\Portal\ListDoctorReviewsRequest;
use App\Http\Requests\Portal\ReplyDoctorReviewRequest;
use App\Models\Review;

class DoctorReviewController extends Controller
{
    use HasActiveDoctor;

    public function index(
        ListDoctorReviewsRequest $request,
        ListDoctorReviewsAction $action
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();

        return $action->handle($request->validated(), $doctor);
    }

    public function toggle(
        Review $review,
        ToggleDoctorReviewAction $action
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();

        return $action->handle($doctor, $review);
    }

    public function reply(
        ReplyDoctorReviewRequest $request,
        Review $review,
        ReplyDoctorReviewAction $action
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();

        return $action->handle(
            $doctor,
            $review,
            $request->validated('doctor_reply')
        );
    }
}