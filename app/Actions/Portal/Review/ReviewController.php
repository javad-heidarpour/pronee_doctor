<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Review\ListDoctorReviewsAction;
use App\Actions\Portal\Review\ReplyDoctorReviewAction;
use App\Actions\Portal\Review\ToggleDoctorReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ReplyReviewRequest;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | نظرات بیماران
    |--------------------------------------------------------------------------
    */

    public function doctorReviews(
        Request $request,
        ListDoctorReviewsAction $action
    ) {
        return $action->handle($request);
    }

    /*
    |--------------------------------------------------------------------------
    | تایید / لغو تایید
    |--------------------------------------------------------------------------
    */

    public function toggleDoctorReview(
        Review $review,
        ToggleDoctorReviewAction $action
    ) {
        return $action->handle($review);
    }

    /*
    |--------------------------------------------------------------------------
    | پاسخ پزشک
    |--------------------------------------------------------------------------
    */

    public function replyDoctorReview(
        ReplyReviewRequest $request,
        Review $review,
        ReplyDoctorReviewAction $action
    ) {
        return $action->handle(
            $request,
            $review
        );
    }

    /*
    |--------------------------------------------------------------------------
    | متدهای قبلی پنل (در صورت وجود)
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        //
    }

    public function toggleApproval(Review $review)
    {
        //
    }

    public function destroy(Review $review)
    {
        //
    }
}