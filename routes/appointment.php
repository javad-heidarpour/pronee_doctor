<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal\PatientController;

Route::prefix('appointments')->name('appointments.')
    ->controller(HomeController::class)
    ->group(function () {
        Route::post('/book', 'bookAppointment')->name('book');
        Route::get('/success/{id}', 'appointmentSuccess')->name('success');
    });
