<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\GalleryController;

Route::prefix('gallery')->name('gallery.')
    ->controller(GalleryController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{image}', 'update')->name('update');
        Route::delete('/{image}', 'destroy')->name('destroy');
    });