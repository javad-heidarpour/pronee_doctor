<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\PostController;

// مدیریت مقالات (Posts)
Route::prefix('posts')->name('posts.')->group(function () {
    Route::get('/', [PostController::class, 'index'])->name('index');
    Route::get('/{post:slug}', [PostController::class, 'show'])->name('show');

    Route::get('/create', [PostController::class, 'create'])->name('create');
    Route::post('/', [PostController::class, 'store'])->name('store');


    // روت‌های ویرایش و نمایش
    Route::get('/{post}/edit', [PostController::class, 'edit'])->name('edit');
    Route::put('/{post}', [PostController::class, 'update'])->name('update'); // متد PUT برای ویرایش
    Route::delete('/{post}', [PostController::class, 'destroy'])->name('destroy');
    Route::patch('/{post}/toggle-pin', [PostController::class, 'togglePin'])->name('toggle-pin');


    Route::get('/{post:slug}', [PostController::class, 'show'])->name('show');


});


Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [PostController::class, 'show'])->name('show');
});
Route::prefix('tags')->name('tags.')->group(function () {
    Route::get('/', [PostController::class, 'show'])->name('show');
});
Route::prefix('authors')->name('authors.')->group(function () {
    Route::get('/', [PostController::class, 'show'])->name('show');
});


