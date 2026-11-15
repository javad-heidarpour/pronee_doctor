<?php

namespace App\Http\Controllers;

use App\Actions\Review\ApproveReviewAction;
use App\Actions\Review\DeleteReviewAction;
use App\Actions\Review\RejectReviewAction;
use App\Actions\Review\ReplyReviewAction;
use App\Actions\Review\StoreReviewAction;
use App\Actions\Review\ShowReviewsAction;

use App\Http\Requests\StoreReviewRequest;

use App\Models\Doctor;
use App\Models\Review;

use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        return app(ShowReviewsAction::class)->handle();
    }
    /**
     * ثبت نظر بیمار
     */
    public function store(
        StoreReviewRequest $request,
        Doctor $doctor,
        StoreReviewAction $action
    ) {
        // return response()->json([
        //     'success' => true,
        //     'message' => 'رسیدم به کنترلر',
        // ]);
        return $action->handle(
            $request,
            $doctor
        );
    }

    /**
     * تایید نظر
     */
    public function approve(
        Review $review,
        ApproveReviewAction $action
    ) {
        return $action->handle($review);
    }

    /**
     * رد نظر
     */
    public function reject(
        Review $review,
        RejectReviewAction $action
    ) {
        return $action->handle($review);
    }

    /**
     * پاسخ پزشک
     */
    public function reply(
        Request $request,
        Review $review,
        ReplyReviewAction $action
    ) {
        return $action->handle(
            $request,
            $review
        );
    }

    /**
     * حذف نظر
     */
    public function destroy(
        Review $review,
        DeleteReviewAction $action
    ) {
        return $action->handle($review);
    }
}