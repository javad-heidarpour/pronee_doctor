<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\DoctorReviewController;

/*
|--------------------------------------------------------------------------
| مدیریت نظرات پزشکان (Doctor Reviews)
|--------------------------------------------------------------------------
| این روت‌ها باید فقط برای پزشک لاگین‌شده در دسترس باشند،
| بنابراین در فایل اصلی داخل middleware('portal.auth') include می‌شوند.
*/

Route::prefix('doctor-reviews')->name('doctor-reviews.')->group(function () {

    Route::get(
        '/',
        [DoctorReviewController::class, 'index']
    )->name('index');

    Route::put(
        '/{review}/toggle',
        [DoctorReviewController::class, 'toggle']
    )->name('toggle');

    Route::put(
        '/{review}/reply',
        [DoctorReviewController::class, 'reply']
    )->name('reply');

});