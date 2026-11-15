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
        Route::post('/send-otp', 'sendOtp')->name('register.sendOtp');

        // تأیید کد موبایل
        Route::post('/verify-otp', 'verifyOtp')->name('register.verifyOtp');

        // ثبت نهایی پزشک و ایجاد سایت
        Route::post('/', 'store')->name('register.store');
    });
