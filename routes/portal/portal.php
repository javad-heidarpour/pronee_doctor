<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\SecurityAuditController;
use App\Http\Controllers\Portal\PaymentReviewController;
use App\Http\Controllers\Portal\SecuritySessionsController;
use App\Http\Controllers\Portal\ClinicFinanceController;

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

        Route::get('/payments/review-queue', [
            PaymentReviewController::class,
            'index'
        ])->name('payments.review-queue');

        Route::post('/payments/{payment}/reconcile', [
            PaymentReviewController::class,
            'reconcile'
        ])->whereNumber('payment')->middleware('throttle:3,1')->name('payments.reconcile');

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

        Route::get('/organizations', [ClinicOrganizationController::class, 'index'])->name('organizations.index');
        Route::post('/organizations', [ClinicOrganizationController::class, 'store'])->name('organizations.store');
        Route::post('/organizations/{organization}/doctors', [ClinicOrganizationController::class, 'inviteDoctor'])->whereNumber('organization')->name('organizations.doctors.invite');
        Route::post('/organizations/{organization}/doctors/accept', [ClinicOrganizationController::class, 'acceptDoctor'])->whereNumber('organization')->name('organizations.doctors.accept');
        Route::post('/organizations/{organization}/clinics', [ClinicOrganizationController::class, 'attachClinic'])->whereNumber('organization')->name('organizations.clinics.attach');

        Route::get('/finance', [ClinicFinanceController::class, 'index'])->name('finance.index');
        Route::post('/finance/treatment-plans', [ClinicFinanceController::class, 'storePlan'])->name('finance.plans.store');
        Route::post('/finance/installments/{installment}/payments', [ClinicFinanceController::class, 'recordPayment'])->whereNumber('installment')->name('finance.installments.payments.store');
        Route::post('/finance/treatment-plans/{plan}/collaterals', [ClinicFinanceController::class, 'storeCollateral'])->whereNumber('plan')->name('finance.collaterals.store');
        Route::put('/finance/collaterals/{collateral}', [ClinicFinanceController::class, 'updateCollateral'])->whereNumber('collateral')->name('finance.collaterals.update');
        Route::post('/finance/expenses', [ClinicFinanceController::class, 'storeExpense'])->name('finance.expenses.store');

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