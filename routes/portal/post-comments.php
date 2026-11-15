<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\PostCommentController;

Route::prefix('post-comments')->name('post-comments.')->group(function () {

    Route::get('/', [PostCommentController::class, 'index'])
        ->name('index');

    Route::put('/{comment}/approve', [PostCommentController::class, 'approve'])
        ->name('approve');

    Route::put('/{comment}/reject', [PostCommentController::class, 'reject'])
        ->name('reject');

    Route::post('/{comment}/reply', [PostCommentController::class, 'reply'])
        ->name('reply');

    Route::delete('/{comment}', [PostCommentController::class, 'destroy'])
        ->name('destroy');

});