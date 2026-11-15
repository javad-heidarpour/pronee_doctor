<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal\PatientController;

Route::prefix('appointments')->name('appointments.')->group(function () {
    Route::post('/book', [HomeController::class, 'bookAppointment'])->name('book');
    Route::get('/success/{id}', [HomeController::class, 'appointmentSuccess'])->name('success');
});
