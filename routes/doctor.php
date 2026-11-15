<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

Route::get('/{slug}', [HomeController::class, 'showDoctor'])
    ->name('portal.HomePage')
    ->where('slug', '^[a-z0-9-]+-?[a-z0-9]+$');