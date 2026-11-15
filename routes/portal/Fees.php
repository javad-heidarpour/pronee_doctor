<?php

use App\Http\Controllers\Portal\FeesController;
use Illuminate\Support\Facades\Route;

Route::prefix('Fees')
    ->name('Fees.')
    ->group(function () {

        Route::get('/', [FeesController::class, 'index'])
            ->name('index');

        Route::put('/', [FeesController::class, 'updateFee'])
            ->name('update');

    });