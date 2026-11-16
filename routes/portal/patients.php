<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\PatientController;
use App\Http\Controllers\Portal\AppointmentController;


// مربوط یه portal/patients/index
Route::prefix('appointments')->name('appointments.')->group(function () {
    Route::put('/{appointment}/status', [PatientController::class, 'updateStatus'])
        ->name('status');

    // ذخیره مودل ویرایش و انتخاب نوع عمل جراحی
    Route::put('/{appointment}/description', [PatientController::class, 'updateDescription'])
        ->name('description');

});


Route::prefix('patients')->name('patients.')->group(function () {
    Route::get('/', [PatientController::class, 'index'])
        ->name('index');

    // دریافت تاریخ‌های آزاد؛ فعلاً مسیر قبلی حفظ می‌شود
    Route::get('/available-dates', [PatientController::class, 'availableDates'])
        ->name('available-dates');

    // دریافت ساعت‌های آزاد از کنترلر جدید
    Route::get('/available-slots', [AppointmentController::class, 'availableSlots'])
        ->name('available-slots');

    // ثبت نوبت توسط منشی، بدون پرداخت آنلاین
    Route::post('/store', [AppointmentController::class, 'store'])
        ->name('store');

    // جزئیات نوبت پس از تعریف مسیرهای ثابت
    Route::get('/{appointment}', [PatientController::class, 'show'])
        ->whereNumber('appointment')
        ->name('show');
});