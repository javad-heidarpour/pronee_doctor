<?php

namespace App\\Http\\Controllers;

use App\\Actions\\Review\\StoreReviewAction;
use App\\Http\\Requests\\StoreReviewRequest;
use App\\Models\\Doctor;

class ReviewController extends Controller
{
    /**
     * ثبت نظر بیمار برای پزشک.
     *
     * سایر عملیات مدیریت نظر در پنل پزشک و مسیرهای محافظت‌شده انجام می‌شوند.
     */
    public function store(
        StoreReviewRequest $request,
        Doctor $doctor,
        StoreReviewAction $action
    ) {
        return $action->handle($request, $doctor);
    }
}
