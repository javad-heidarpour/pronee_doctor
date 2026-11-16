<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SmsController;


Route::prefix('/sms')->name('sms.')->group(function () {
    Route::get('/visit/{visit_number}', [SmsController::class, 'visit'])
        ->whereNumber('visit_number')
        ->middleware('throttle:5,1')
        ->name('visit');
Route::get('/Confirmation/{visit_number}', [SmsController::class, 'AppointmentConfirmationSMS'])
        ->whereNumber('visit_number')
        ->middleware('throttle:5,1')
        ->name('Confirmation');
        
});