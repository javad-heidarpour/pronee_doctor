<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReviewController;

/*
|--------------------------------------------------------------------------
| ثبت نظر برای پزشک
|--------------------------------------------------------------------------
*/

Route::post(
    '/{doctor:slug}/review',
    [ReviewController::class, 'store']
)->middleware('throttle:5,1')->name('review.store');
