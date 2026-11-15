<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\AppointmentPaymentController;

Route::prefix('visit')->name('visit.')->group(function () {

    // نمایش اطلاعات نوبت
    Route::get('/{visit_number}', [VisitController::class, 'show',])
        ->whereNumber('visit_number')
        ->name('show');

});

Route::prefix('payment')->name('payment.')->group(function () {

    // شروع پرداخت نوبت از مسیر پرداخت
    Route::post('/{visit_number}/pay', [AppointmentPaymentController::class, 'pay',])
        ->whereNumber('visit_number')
        ->name('pay');

    // بازگشت از درگاه سامان‌کیش (GET و POST)
    Route::match(['GET', 'POST'], '/callback', [AppointmentPaymentController::class, 'callback',])
        ->name('callback');
});