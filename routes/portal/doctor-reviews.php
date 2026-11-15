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

Route::prefix('doctor-reviews')->name('doctor-reviews.')
    ->controller(DoctorReviewController::class)
    ->group(function () {

        Route::get('/', 'index')->name('index');
        Route::put('/{review}/toggle', 'toggle')->name('toggle');
        Route::put('/{review}/reply', 'reply')->name('reply');

    });