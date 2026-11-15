<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\ShiftController;

Route::prefix('shifts')->name('shifts.')->group(function () {

    Route::get('/', [ShiftController::class, 'index'])->name('index');
    Route::post('/', [ShiftController::class, 'store'])->name('store');
    Route::put('/{slot}', [ShiftController::class, 'update'])->name('update');
    Route::put('/{slot}/toggle', [ShiftController::class, 'toggle'])->name('toggle');
    Route::delete('/{slot}', [ShiftController::class, 'destroy'])->name('destroy');
    Route::post('/generate', [ShiftController::class, 'generateSlots'])->name('generate');
});