<?php

use App\\Http\\Controllers\\AppointmentSatisfactionController;
use Illuminate\\Support\\Facades\\Route;

Route::middleware('signed')->group(function (): void {
    Route::get('/satisfaction/{appointment}', [AppointmentSatisfactionController::class, 'show'])
        ->whereNumber('appointment')->name('patient.satisfaction.show');
    Route::post('/satisfaction/{appointment}', [AppointmentSatisfactionController::class, 'store'])
        ->whereNumber('appointment')->middleware('throttle:5,1')->name('patient.satisfaction.store');
});
