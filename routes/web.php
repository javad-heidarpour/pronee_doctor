<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal\ClinicMarketingController;
use App\Http\Controllers\PatientHealthController;

require __DIR__ . '/template.php';
require __DIR__ . '/portal/portal.php';
require __DIR__ . '/sms.php';
require __DIR__ . '/payment.php';
require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Home Routes
|--------------------------------------------------------------------------
*/

Route::get('/c/{code}', [ClinicMarketingController::class, 'track'])->where('code', '[A-Za-z0-9]{8}')->name('growth.track');

Route::get('/', [HomeController::class, 'Home'])
    ->name('home');

Route::middleware('auth:web')->prefix('my-health')->name('patient.health.')->controller(PatientHealthController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/access-request', 'requestAccess')->middleware('throttle:5,1')->name('access.request');
    Route::post('/observations', 'storeObservation')->middleware('throttle:30,1')->name('observations.store');
    Route::delete('/observations/{observation}', 'destroyObservation')->whereNumber('observation')->middleware('throttle:30,1')->name('observations.destroy');
    Route::post('/caregivers', 'grantCaregiver')->middleware('throttle:5,1')->name('caregivers.store');
    Route::delete('/caregivers/{access}', 'revokeCaregiver')->whereNumber('access')->name('caregivers.revoke');
});

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
require __DIR__ . '/review.php';
require __DIR__ . '/home.php';

