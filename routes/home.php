<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomePage\Home1Controller;

Route::prefix('/{slug}')->controller(Home1Controller::class)->group(function () {

    Route::get('/booking', 'booking')->name('booking');
    Route::get('/services', 'services')->name('services');
    Route::get('/surgeries', 'Surgeries')->name('Surgeries');
    Route::get('/contact', 'contact')->name('contact');
    Route::get('/tips', 'tips')->name('tips');
    Route::get('/galleries', 'galleries')->name('galleries');
    Route::get('/reviews', 'Reviews')->name('Reviews');
    Route::get('/About', 'About')->name('About');
    Route::get('/WorkingHours', 'WorkingHours')->name('WorkingHours');


});