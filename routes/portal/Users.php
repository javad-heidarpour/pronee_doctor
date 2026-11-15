<?php

use App\Http\Controllers\Portal\UsersController;
use Illuminate\Support\Facades\Route;

Route::prefix('/users')
    ->name('users.')
    ->controller(UsersController::class)
    ->group(function () {

        Route::get('/index', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');

        Route::get('/{user}/edit', 'edit')->name('edit');
        Route::patch('/{user}/status', 'status')->name('status');
        Route::patch('/{user}', 'update')->name('update');
        Route::delete('/{user}', 'destroy')->name('destroy')->withTrashed();

        Route::get('/{user}/assignments', 'assignments')
            ->name('assignments');

        Route::put('/{user}/assignments', 'updateAssignments')
            ->name('assignments.update');
    });