<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\DashboardController;

/*
|--------------------------------------------------------------------------
| Portal Routes
|--------------------------------------------------------------------------
*/

Route::prefix('portal')->name('portal.')->group(function () {


    Route::post('/select-doctor/{doctor}', [
        DashboardController::class,
        'selectDoctor'
    ])->name('select-doctor');
    /*
    |--------------------------------------------------------------------------
    | مدیریت نظرات مقالات
    |--------------------------------------------------------------------------
    */

    require __DIR__ . '/post-comments.php';


    /*
    |--------------------------------------------------------------------------
    | ورود هوشمند به پنل پزشک
    |--------------------------------------------------------------------------
    */

    Route::get('/', function () {

        // اگر پزشک وارد شده باشد
        if (auth('web')->check()) {
            return redirect()->route('portal.dashboard');
        }

        // اگر پزشک وارد نشده باشد
        return redirect()->route('portal.login');

    })->name('index');


    /*
    |--------------------------------------------------------------------------
    | صفحات عمومی پزشکان
    |--------------------------------------------------------------------------
    */

    Route::get('/doctors/{slug}', [
        HomeController::class,
        'HomePage'
    ])->name('doctors.HomePage');


    /*
    |--------------------------------------------------------------------------
    | روت‌های ورود (مهمان)
    |--------------------------------------------------------------------------
    */

    Route::middleware('guest:doctor')->group(function () {

        Route::get('/login', [
            AuthController::class,
            'showLoginForm'
        ])->name('login');

        Route::post('/login', [
            AuthController::class,
            'login'
        ])->name('login.submit');

    });


    /*
    |--------------------------------------------------------------------------
    | روت‌های پنل پزشک (احراز هویت شده)
    |--------------------------------------------------------------------------
    */

    Route::middleware('portal.auth')->group(function () {

        // خروج از حساب پزشک
        Route::post('/logout', [
            AuthController::class,
            'logout'
        ])->name('logout');


        // داشبورد
        Route::get('/dashboard', [
            DashboardController::class,
            'index'
        ])->name('dashboard');


        // پروفایل پزشک
        require __DIR__ . '/Profile.php';


        // مطب‌ها
        require __DIR__ . '/clinics.php';


        // ساعات کاری
        require __DIR__ . '/working-hours.php';


        // گالری
        require __DIR__ . '/gallery.php';


        // بیماران و مدیریت نوبت‌ها
        require __DIR__ . '/patients.php';


        // شیفت‌ها و اسلات‌های زمانی
        require __DIR__ . '/shifts.php';


        // نظرات پزشک
        require __DIR__ . '/doctor-reviews.php';


        // مقالات
        require __DIR__ . '/post.php';


        // تعرفه‌ها
        require __DIR__ . '/Fees.php';
        require __DIR__ . '/Users.php';

    });

});
