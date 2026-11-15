<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\DoctorReviews\ListDoctorReviewsAction;
use App\Actions\Portal\DoctorReviews\ReplyDoctorReviewAction;
use App\Actions\Portal\DoctorReviews\ToggleDoctorReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ReplyDoctorReviewRequest;
use App\Models\Review;
use Illuminate\Http\Request;

class DoctorReviewController extends Controller
{
    public function index(
        Request $request,
        ListDoctorReviewsAction $action
    ) {
        return $action->handle($request);
    }

    public function toggle(
        Review $review,
        ToggleDoctorReviewAction $action
    ) {
        return $action->handle($review);
    }

    public function reply(
        ReplyDoctorReviewRequest $request,
        Review $review,
        ReplyDoctorReviewAction $action
    ) {
        return $action->handle(
            $request,
            $review
        );
    }
}