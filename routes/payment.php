<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\AppointmentPaymentController;

Route::prefix('visit')->name('visit.')->controller(VisitController::class)->group(function () {

    // نمایش اطلاعات نوبت
    Route::get('/{visit_number}', 'show')
        ->whereNumber('visit_number')
        ->name('show');

});

Route::prefix('payment')->name('payment.')->controller(AppointmentPaymentController::class)->group(function () {

    // شروع پرداخت نوبت از مسیر پرداخت
    Route::post('/{visit_number}/pay', 'pay')
        ->whereNumber('visit_number')
        ->name('pay');

    // بازگشت از درگاه سامان‌کیش (GET و POST)
    Route::match(['GET', 'POST'], '/callback', 'callback')
        ->name('callback');
});