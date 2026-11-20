<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\AppointmentPaymentController;

Route::prefix('visit')->name('visit.')->controller(VisitController::class)->group(function () {

    // نمایش اطلاعات نوبت
    Route::get('/{visit_number}', 'show')
        ->whereNumber('visit_number')
        ->middleware('signed')
        ->name('show');

    Route::post('/{visit_number}/cancel', 'cancel')
        ->whereNumber('visit_number')
        ->middleware(['signed', 'throttle:5,1'])
        ->name('cancel');

    Route::post('/{visit_number}/reschedule', 'reschedule')
        ->whereNumber('visit_number')
        ->middleware(['signed', 'throttle:5,1'])
        ->name('reschedule');

});

Route::prefix('payment')->name('payment.')->controller(AppointmentPaymentController::class)->group(function () {

    // شروع پرداخت نوبت از مسیر پرداخت
    Route::post('/{visit_number}/pay', 'pay')
        ->whereNumber('visit_number')
        ->middleware(['signed', 'throttle:5,1'])
        ->name('pay');

    // بازگشت از درگاه سامان‌کیش (GET و POST)
    Route::match(['GET', 'POST'], '/callback', 'callback')
        ->name('callback');
});