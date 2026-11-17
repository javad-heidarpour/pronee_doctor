<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\SecurityAuditController;

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/', function () {
        if (auth('web')->check()) {
            return redirect()->route('portal.dashboard');
        }

        return redirect()->route('portal.login');
    })->name('index');

    Route::get('/doctors/{slug}', [
        HomeController::class,
        'HomePage'
    ])->name('doctors.HomePage');

    // Login uses the same web guard as the portal authentication middleware.
    Route::middleware('guest:web')->group(function () {
        Route::get('/login', [
            AuthController::class,
            'showLoginForm'
        ])->name('login');

        Route::post('/login', [
            AuthController::class,
            'login'
        ])->middleware('throttle:5,1')->name('login.submit');
    });

    Route::middleware('portal.auth')->group(function () {
        Route::post('/select-doctor/{doctor}', [
            DashboardController::class,
            'selectDoctor'
        ])->name('select-doctor');

        Route::post('/logout', [
            AuthController::class,
            'logout'
        ])->name('logout');

        Route::get('/security/audit-logs', [
            SecurityAuditController::class,
            'index'
        ])->name('security.audit-logs');

        Route::get('/security/sessions', [
            SecuritySessionsController::class,
            'index'
        ])->name('security.sessions');

        Route::delete('/security/sessions/{session}', [
            SecuritySessionsController::class,
            'destroy'
        ])->where('session', '[a-f0-9]{64}')->name('security.sessions.destroy');

        Route::get('/dashboard', [
            DashboardController::class,
            'index'
        ])->name('dashboard');

        require __DIR__ . '/Profile.php';
        require __DIR__ . '/clinics.php';
        require __DIR__ . '/working-hours.php';
        require __DIR__ . '/gallery.php';
        require __DIR__ . '/patients.php';
        require __DIR__ . '/shifts.php';
        require __DIR__ . '/doctor-reviews.php';
        require __DIR__ . '/Fees.php';
        require __DIR__ . '/Users.php';
    });
});