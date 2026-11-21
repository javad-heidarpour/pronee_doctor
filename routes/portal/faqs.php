<?php

use App\\Http\\Controllers\\Portal\\DoctorFaqController;
use Illuminate\\Support\\Facades\\Route;

Route::get('/faqs', [DoctorFaqController::class, 'index'])->name('faqs.index');
Route::post('/faqs', [DoctorFaqController::class, 'store'])->middleware('throttle:20,1')->name('faqs.store');
Route::put('/faqs/{faq}', [DoctorFaqController::class, 'update'])->whereNumber('faq')->middleware('throttle:20,1')->name('faqs.update');
Route::delete('/faqs/{faq}', [DoctorFaqController::class, 'destroy'])->whereNumber('faq')->name('faqs.destroy');
