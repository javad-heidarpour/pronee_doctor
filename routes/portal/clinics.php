<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\ClinicController;

Route::prefix('clinics')->name('clinics.')->group(function () {
    Route::get('/', [ClinicController::class, 'index'])->name('index');
    Route::post('/', [ClinicController::class, 'store'])->name('store');
    Route::put('/{clinic}', [ClinicController::class, 'update'])->name('update');
    Route::delete('/{clinic}', [ClinicController::class, 'destroy'])->name('destroy');
});