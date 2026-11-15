<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\WorkingHourController;

Route::prefix('working-hours')->name('working-hours.')->group(function () {

    Route::get('/', [WorkingHourController::class, 'index'])->name('index');
    Route::post('/', [WorkingHourController::class, 'store'])->name('store');
    Route::put('/{workingHour}', [WorkingHourController::class, 'update'])->name('update');
    Route::put('/{workingHour}/toggle', [WorkingHourController::class, 'toggle'])->name('toggle');
    Route::delete('/{workingHour}', [WorkingHourController::class, 'destroy'])->name('destroy');
});