<?php

use App\\Http\\Controllers\\PublicDoctorFaqController;
use Illuminate\\Support\\Facades\\Route;

Route::get('/{doctor:slug}/faqs', [PublicDoctorFaqController::class, 'show'])
    ->name('doctor.faqs');
