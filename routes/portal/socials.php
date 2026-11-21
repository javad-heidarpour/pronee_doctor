<?php

use App\\Http\\Controllers\\Portal\\DoctorSocialController;
use Illuminate\\Support\\Facades\\Route;

Route::get('/socials', [DoctorSocialController::class, 'index'])->name('socials.index');
Route::post('/socials', [DoctorSocialController::class, 'store'])->middleware('throttle:20,1')->name('socials.store');
Route::put('/socials/{social}', [DoctorSocialController::class, 'update'])->whereNumber('social')->middleware('throttle:20,1')->name('socials.update');
Route::delete('/socials/{social}', [DoctorSocialController::class, 'destroy'])->whereNumber('social')->name('socials.destroy');
