<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SmsController;

Route::prefix('/sms')->name('sms.')->group(function () {
    // ارسال پیامک عملیاتی باید POST باشد؛ GET نباید باعث ارسال پیامک شود.
    Route::post('/visit/{visit_number}', [SmsController::class, 'visit'])
        ->whereNumber('visit_number')
        ->middleware('throttle:5,1')
        ->name('visit');
});
