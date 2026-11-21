<?php

use App\\Http\\Controllers\\SupportTicketController;
use Illuminate\\Support\\Facades\\Route;

Route::get('/support', [SupportTicketController::class, 'create'])->name('support.tickets.create');
Route::post('/support/tickets', [SupportTicketController::class, 'store'])->middleware('throttle:5,1')->name('support.tickets.store');
