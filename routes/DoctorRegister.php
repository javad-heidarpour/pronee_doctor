<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\DoctorRegistrationController;



Route::prefix('/register/doctor')
    ->name('doctor.')
    ->controller(DoctorRegistrationController::class)
    ->group(function () {

        // نمایش فرم ثبت‌نام پزشک
        Route::get('/', 'create')->name('register');

        // ارسال کد تأیید موبایل
        Route::post('/send-otp', 'sendOtp')->middleware('throttle:5,1')->name('register.sendOtp');

        // تأیید کد موبایل
        Route::post('/verify-otp', 'verifyOtp')->middleware('throttle:10,1')->name('register.verifyOtp');

        // ثبت نهایی پزشک و ایجاد سایت
        Route::post('/', 'store')->middleware('throttle:5,1')->name('register.store');
    });
