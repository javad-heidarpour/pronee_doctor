<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::prefix('template')->name('template.')->group(function () {
    Route::get('/index0', function () {
        // return 1;
        return Inertia::render('HomePage/Home1');
    })->name('index0');
    Route::get('/index1', function () {
        return Inertia::render('HomeHtml/Index1');
    })->name('index1');
    Route::get('/index2', function () {
        return Inertia::render('HomeHtml/Index2');
    })->name('index3');
    Route::get('/index3', function () {
        return Inertia::render('HomeHtml/Index3');
    })->name('index3');
    Route::get('/index4', function () {
        return Inertia::render('HomeHtml/Index4');
    })->name('index5');
    Route::get('/index5', function () {
        return Inertia::render('HomeHtml/Index5');
    })->name('index5');
});