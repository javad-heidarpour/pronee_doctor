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

// صفحه اصلی - قالب جدید Home1
// قالب قبلی پزشک
Route::get('/dr-farhad-vafaei', [HomeController::class, 'showDoctor'])
    ->name('home1');

// قالب جدید پزشک
Route::get('/', [HomeController::class, 'Home1'])
    ->name('home');



/*
|--------------------------------------------------------------------------
| سایر Route ها
|--------------------------------------------------------------------------
*/

require __DIR__ . '/appointment.php';
require __DIR__ . '/blog.php';
require __DIR__ . '/doctor.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/review.php';
require __DIR__ . '/home.php';

