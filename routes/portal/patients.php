<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\PatientController;
use App\Http\Controllers\Portal\AppointmentController;
use App\Http\Controllers\Portal\PatientFollowUpController;
use App\Http\Controllers\Portal\PatientEncounterController;
use App\Http\Controllers\Portal\ClinicalRiskRadarController;
use App\Http\Controllers\Portal\PredictivePatientController;
use App\Http\Controllers\Portal\PatientCarePathwayController;
use App\Http\Controllers\Portal\Patient360Controller;
use App\Http\Controllers\Portal\HealthInteropController;
use App\Http\Controllers\Portal\PatientProblemController;
use App\Http\Controllers\Portal\PatientDocumentController;
use App\Http\Controllers\Portal\PatientConsentController;
use App\Http\Controllers\Portal\PatientInsuranceController;
use App\Http\Controllers\Portal\NoShowRiskController;
use App\Http\Controllers\Portal\PopulationHealthController;


Route::get('/health-interop', [HealthInteropController::class, 'index'])->name('health-interop.index');
Route::post('/health-interop/patients/{patient}/identity-check', [HealthInteropController::class, 'validateIdentity'])->whereNumber('patient')->middleware('throttle:20,1')->name('health-interop.identity-check');
Route::post('/health-interop/patients/{patient}/insurance-check', [HealthInteropController::class, 'verifyInsurance'])->whereNumber('patient')->middleware('throttle:10,1')->name('health-interop.insurance-check');
Route::post('/health-interop/appointments/{appointment}', [HealthInteropController::class, 'queueAppointment'])->whereNumber('appointment')->middleware('throttle:20,1')->name('health-interop.appointments.queue');
Route::post('/health-interop/encounters/{encounter}', [HealthInteropController::class, 'queueEncounter'])->whereNumber('encounter')->middleware('throttle:20,1')->name('health-interop.encounters.queue');

Route::get('/clinical-risk', [ClinicalRiskRadarController::class, 'index'])->name('clinical-risk.index');
Route::get('/predictive-patients', [PredictivePatientController::class, 'index'])->name('predictive-patients.index');
Route::get('/no-show-risk', [NoShowRiskController::class, 'index'])->name('no-show-risk.index');
Route::get('/population-health', [PopulationHealthController::class, 'index'])->name('population-health.index');
Route::patch('/appointments/{appointment}/attendance-outcome', [NoShowRiskController::class, 'updateOutcome'])->whereNumber('appointment')->middleware('throttle:20,1')->name('appointments.attendance-outcome');
Route::get('/care-pathways', [PatientCarePathwayController::class, 'index'])->name('care-pathways.index');
Route::post('/care-pathways', [PatientCarePathwayController::class, 'store'])->middleware('throttle:20,1')->name('care-pathways.store');
Route::post('/care-pathways/{pathway}/activate', [PatientCarePathwayController::class, 'activate'])->whereNumber('pathway')->name('care-pathways.activate');
Route::post('/care-pathway-steps/{step}/verify', [PatientCarePathwayController::class, 'verifyStep'])->whereNumber('step')->name('care-pathway-steps.verify');

// مربوط به portal/patients/index
Route::prefix('appointments')->name('appointments.')->group(function () {
    Route::put('/{appointment}/status', [PatientController::class, 'updateStatus'])
        ->name('status');

    // ذخیره مودل ویرایش و انتخاب نوع عمل جراحی
    Route::put('/{appointment}/description', [PatientController::class, 'updateDescription'])
        ->name('description');

});


Route::prefix('encounters')->name('encounters.')->group(function () {
    Route::get('/appointments/{appointment}', [PatientEncounterController::class, 'index'])->whereNumber('appointment')->name('index');
    Route::get('/appointments/{appointment}/create', [PatientEncounterController::class, 'create'])->whereNumber('appointment')->name('create');
    Route::post('/appointments/{appointment}/extract', [PatientEncounterController::class, 'extractText'])->whereNumber('appointment')->middleware('throttle:10,1')->name('extract');
    Route::post('/appointments/{appointment}', [PatientEncounterController::class, 'store'])->whereNumber('appointment')->name('store');
    Route::get('/{encounter}/amend', [PatientEncounterController::class, 'amend'])->whereNumber('encounter')->name('amend');
    Route::get('/{encounter}', [PatientEncounterController::class, 'show'])->whereNumber('encounter')->name('show');
    Route::put('/{encounter}', [PatientEncounterController::class, 'update'])->whereNumber('encounter')->name('update');
    Route::post('/{encounter}/sign', [PatientEncounterController::class, 'sign'])->whereNumber('encounter')->name('sign');
});

Route::prefix('follow-ups')->name('follow-ups.')->group(function () {
    Route::get('/', [PatientFollowUpController::class, 'index'])->name('index');
    Route::post('/', [PatientFollowUpController::class, 'store'])->name('store');
    Route::put('/{task}', [PatientFollowUpController::class, 'update'])->whereNumber('task')->name('update');
    Route::post('/templates', [PatientFollowUpController::class, 'storeTemplate'])->name('templates.store');
    Route::put('/templates/{template}', [PatientFollowUpController::class, 'updateTemplate'])->whereNumber('template')->name('templates.update');
    Route::delete('/templates/{template}', [PatientFollowUpController::class, 'destroyTemplate'])->whereNumber('template')->name('templates.destroy');
});

Route::prefix('patients')->name('patients.')->group(function () {
    Route::get('/{patient}/360', [Patient360Controller::class, 'show'])->whereNumber('patient')->name('360');
    Route::get('/{patient}/consents', [PatientConsentController::class, 'index'])->whereNumber('patient')->name('consents.index');
    Route::get('/{patient}/insurance', [PatientInsuranceController::class, 'index'])->whereNumber('patient')->name('insurance.index');
    Route::post('/{patient}/insurance', [PatientInsuranceController::class, 'store'])->whereNumber('patient')->middleware('throttle:20,1')->name('insurance.store');
    Route::post('/{patient}/consents', [PatientConsentController::class, 'store'])->whereNumber('patient')->middleware('throttle:20,1')->name('consents.store');
    Route::get('/{patient}/documents', [PatientDocumentController::class, 'index'])->whereNumber('patient')->name('documents.index');
    Route::post('/{patient}/documents', [PatientDocumentController::class, 'store'])->whereNumber('patient')->middleware('throttle:20,1')->name('documents.store');
    Route::get('/{patient}/documents/{document}/download', [PatientDocumentController::class, 'download'])->whereNumber('patient')->whereNumber('document')->middleware('throttle:30,1')->name('documents.download');
    Route::patch('/{patient}/documents/{document}/archive', [PatientDocumentController::class, 'archive'])->whereNumber('patient')->whereNumber('document')->name('documents.archive');
    Route::get('/directory/record/{appointment}', [PatientController::class, 'record'])
        ->whereNumber('appointment')
        ->name('directory.record');

    Route::post('/{patient}/vital-signs', [PatientController::class, 'storeVitalSign'])
        ->whereNumber('patient')
        ->name('vital-signs.store');

    Route::post('/{patient}/problems', [PatientProblemController::class, 'store'])
        ->whereNumber('patient')
        ->name('problems.store');

    Route::put('/{patient}/problems/{problem}', [PatientProblemController::class, 'update'])
        ->whereNumber('patient')
        ->whereNumber('problem')
        ->name('problems.update');

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