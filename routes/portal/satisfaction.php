<?php

use App\\Http\\Controllers\\Portal\\AppointmentSatisfactionReportController;
use Illuminate\\Support\\Facades\\Route;

Route::get('/satisfaction', [AppointmentSatisfactionReportController::class, 'index'])->name('satisfaction.index');
