<?php

use App\Http\Controllers\Portal\FeesController;
use Illuminate\Support\Facades\Route;

Route::prefix('Fees')
    ->name('Fees.')
    ->controller(FeesController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::put('/', 'updateFee')->name('update');
    });