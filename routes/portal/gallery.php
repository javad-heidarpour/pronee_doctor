<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\GalleryController;

Route::prefix('gallery')->name('gallery.')->group(function () {
    Route::get('/', [GalleryController::class, 'index'])->name('index');
    Route::post('/', [GalleryController::class, 'store'])->name('store');
    Route::put('/{image}', [GalleryController::class, 'update'])->name('update');
    Route::delete('/{image}', [GalleryController::class, 'destroy'])->name('destroy');
});