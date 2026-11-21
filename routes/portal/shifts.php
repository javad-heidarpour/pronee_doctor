<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\ShiftController;

Route::prefix('shifts')->name('shifts.')
    ->controller(ShiftController::class)
    ->group(function () {

        Route::get('/', 'index')->name('index');\n        Route::get('/capacity', [ClinicCapacityController::class, 'index'])->name('capacity');
        Route::post('/', 'store')->name('store');
        Route::put('/{slot}', 'update')->name('update');
        Route::put('/{slot}/toggle', 'toggle')->name('toggle');
        Route::delete('/{slot}', 'destroy')->name('destroy');
        Route::post('/generate', 'generateSlots')->name('generate');
    });