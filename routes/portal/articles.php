<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\DoctorArticleController;

Route::prefix('articles')->name('articles.')->controller(DoctorArticleController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->middleware('throttle:20,1')->name('store');
    Route::put('/{article}', 'update')->whereNumber('article')->middleware('throttle:20,1')->name('update');
    Route::patch('/{article}/publish', 'publish')->whereNumber('article')->name('publish');
    Route::patch('/{article}/unpublish', 'unpublish')->whereNumber('article')->name('unpublish');
    Route::delete('/{article}', 'destroy')->whereNumber('article')->name('destroy');
});
