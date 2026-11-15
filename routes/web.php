<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

require __DIR__ . '/template.php';
require __DIR__ . '/portal/portal.php';
require __DIR__ . '/sms.php';
require __DIR__ . '/payment.php';

/*
|--------------------------------------------------------------------------
| Home Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'Home'])
    ->name('home');

Route::get('/{doctor:slug}', [HomeController::class, 'showDoctor'])
    ->name('home1');

/*
|--------------------------------------------------------------------------
| سایر Route ها
|--------------------------------------------------------------------------
*/

require __DIR__ . '/DoctorRegister.php';
require __DIR__ . '/appointment.php';
require __DIR__ . '/doctor.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/review.php';
require __DIR__ . '/home.php';

