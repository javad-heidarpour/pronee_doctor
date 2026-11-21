<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal\ClinicMarketingController;
use App\Http\Controllers\PatientHealthController;
use App\Http\Controllers\PatientMedicationController;
use App\Http\Controllers\PatientConsentSigningController;

require __DIR__ . '/template.php';
require __DIR__ . '/portal/portal.php';
require __DIR__ . '/sms.php';
require __DIR__ . '/payment.php';
require __DIR__ . '/auth.php';

/*
|---------------------------------------------------------------------------
| Home Routes
|---------------------------------------------------------------------------
*/

Route::get('/c/{code}', [ClinicMarketingController::class, 'track'])->where('code', '[A-Za-z0-9]{8}')->name('growth.track');

Route::get('/', [HomeController::class, 'Home'])
    ->name('home');

// Public product introduction. Keep this before the dynamic doctor slug route.
Route::get('/about-proneex', fn () => Inertia::render('AboutProneex'))
    ->name('about.proneex');

Route::middleware('auth:web')->prefix('my-health')->name('patient.health.')->controller(PatientHealthController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/consents/{consent}', [PatientConsentSigningController::class, 'show'])->whereNumber('consent')->name('consents.show');
    Route::post('/consents/{consent}/sign', [PatientConsentSigningController::class, 'sign'])->whereNumber('consent')->middleware('throttle:10,1')->name('consents.sign');
    Route::post('/access-request', 'requestAccess')->middleware('throttle:5,1')->name('access.request');
    Route::post('/medications', [PatientMedicationController::class, 'store'])->middleware('throttle:10,1')->name('medications.store');
    Route::post('/medications/{plan}/doses', [PatientMedicationController::class, 'recordDose'])->whereNumber('plan')->middleware('throttle:30,1')->name('medications.doses.store');
    Route::post('/medications/{plan}/pause', [PatientMedicationController::class, 'pause'])->whereNumber('plan')->middleware('throttle:10,1')->name('medications.pause');
    Route::post('/pathways/{pathway}/steps/{step}/complete', 'reportCarePathwayStep')->whereNumber('pathway')->whereNumber('step')->middleware('throttle:20,1')->name('pathways.steps.complete');
    Route::post('/observations', 'storeObservation')->middleware('throttle:30,1')->name('observations.store');
    Route::delete('/observations/{observation}', 'destroyObservation')->whereNumber('observation')->middleware('throttle:30,1')->name('observations.destroy');
    Route::post('/caregivers', 'grantCaregiver')->middleware('throttle:5,1')->name('caregivers.store');
    Route::delete('/caregivers/{access}', 'revokeCaregiver')->whereNumber('access')->name('caregivers.revoke');
});

Route::get('/{doctor:slug}', [HomeController::class, 'showDoctor'])
    ->name('home1');

/*
|---------------------------------------------------------------------------
| سایر Route ها
|---------------------------------------------------------------------------
*/

require __DIR__ . '/DoctorRegister.php';
require __DIR__ . '/appointment.php';
require __DIR__ . '/doctor.php';
require __DIR__ . '/review.php';
require __DIR__ . '/articles.php';
require __DIR__ . '/faqs.php';
require __DIR__ . '/home.php';
require __DIR__ . '/support.php';

