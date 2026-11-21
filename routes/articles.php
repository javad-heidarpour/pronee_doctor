<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicDoctorArticleController;

Route::get('/doctors/{doctor:slug}/articles', [PublicDoctorArticleController::class, 'index'])
    ->name('doctors.articles.index');
