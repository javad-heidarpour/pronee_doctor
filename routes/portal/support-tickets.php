<?php

use App\\Http\\Controllers\\Portal\\SupportTicketController;
use Illuminate\\Support\\Facades\\Route;

Route::get('/support-tickets', [SupportTicketController::class, 'index'])->name('support-tickets.index');
Route::patch('/support-tickets/{ticket}', [SupportTicketController::class, 'respond'])->whereNumber('ticket')->middleware('throttle:20,1')->name('support-tickets.respond');
