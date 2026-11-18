<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\PatientController;
use App\Http\Controllers\Portal\AppointmentController;
use App\Http\Controllers\Portal\PatientFollowUpController;


// مربوط یه portal/patients/index
Route::prefix('appointments')->name('appointments.')->group(function () {
    Route::put('/{appointment}/status', [PatientController::class, 'updateStatus'])
        ->name('status');

    // ذخیره مودل ویرایش و انتخاب نوع عمل جراحی
    Route::put('/{appointment}/description', [PatientController::class, 'updateDescription'])
        ->name('description');

});


Route::prefix('follow-ups')->name('follow-ups.')->group(function () {
    Route::get('/', [PatientFollowUpController::class, 'index'])->name('index');
    Route::post('/', [PatientFollowUpController::class, 'store'])->name('store');
    Route::put('/{task}', [PatientFollowUpController::class, 'update'])->whereNumber('task')->name('update');
    Route::post('/templates', [PatientFollowUpController::class, 'storeTemplate'])->name('templates.store');
    Route::delete('/templates/{template}', [PatientFollowUpController::class, 'destroyTemplate'])->whereNumber('template')->name('templates.destroy');
});

Route::prefix('patients')->name('patients.')->group(function () {
    Route::get('/directory/record/{appointment}', [PatientController::class, 'record'])
        ->whereNumber('appointment')
        ->name('directory.record');

    Route::post('/{patient}/vital-signs', [PatientController::class, 'storeVitalSign'])
        ->whereNumber('patient')
        ->name('vital-signs.store');

    Route::post('/{patient}/clinical-records', [PatientController::class, 'storeClinicalRecord'])
        ->whereNumber('patient')
        ->name('clinical-records.store');

    Route::put('/{patient}/clinical-records/{record}', [PatientController::class, 'updateClinicalRecord'])
        ->whereNumber('patient')
        ->whereNumber('record')
        ->name('clinical-records.update');

    Route::delete('/{patient}/clinical-records/{record}', [PatientController::class, 'archiveClinicalRecord'])
        ->whereNumber('patient')
        ->whereNumber('record')
        ->name('clinical-records.archive');

    Route::post('/{patient}/clinical-records/{record}/restore', [PatientController::class, 'restoreClinicalRecord'])
        ->whereNumber('patient')
        ->whereNumber('record')
        ->name('clinical-records.restore');

    Route::get('/directory', [PatientController::class, 'directory'])
        ->name('directory');

    Route::get('/', [PatientController::class, 'index'])
        ->name('index');

    Route::get('/export', [PatientController::class, 'export'])
        ->name('export');

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