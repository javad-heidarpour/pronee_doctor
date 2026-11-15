<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\ClinicController;

Route::prefix('clinics')->name('clinics.')
    ->controller(ClinicController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{clinic}', 'update')->name('update');
        Route::delete('/{clinic}', 'destroy')->name('destroy');
    });