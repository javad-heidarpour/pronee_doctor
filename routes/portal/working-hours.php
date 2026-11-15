<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\WorkingHourController;

Route::prefix('working-hours')->name('working-hours.')
    ->controller(WorkingHourController::class)
    ->group(function () {

        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{workingHour}', 'update')->name('update');
        Route::put('/{workingHour}/toggle', 'toggle')->name('toggle');
        Route::delete('/{workingHour}', 'destroy')->name('destroy');
    });