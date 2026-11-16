<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\DoctorReviews\ListDoctorReviewsAction;
use App\Actions\Portal\DoctorReviews\ReplyDoctorReviewAction;
use App\Actions\Portal\DoctorReviews\ToggleDoctorReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Http\Requests\Portal\ReplyDoctorReviewRequest;
use App\Models\Review;
use Illuminate\Http\Request;

class DoctorReviewController extends Controller
{
    use HasActiveDoctor;

    public function index(
        Request $request,
        ListDoctorReviewsAction $action
    ) {
        return $action->handle($request, $this->currentDoctor());
    }

    public function toggle(
        Review $review,
        ToggleDoctorReviewAction $action
    ) {
        return $action->handle($this->currentDoctor(), $review);
    }

    public function reply(
        ReplyDoctorReviewRequest $request,
        Review $review,
        ReplyDoctorReviewAction $action
    ) {
        return $action->handle(
            $request,
            $this->currentDoctor(),
            $review
        );
    }
}