<?php
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('profile')->name('profile.')
    ->controller(ProfileController::class)
    ->group(function () {

        Route::get('/preview', 'preview')->name('preview');
        Route::post('/honor-badges', 'updateHonorBadges')->name('honor-badges.update');
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
        Route::put('/password', 'updatePassword')->name('password');
        Route::delete('/profile', 'destroy')->name('destroy');

    });
