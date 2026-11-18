<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('appointments:expire-pending')
    ->everyMinute()
    ->withoutOverlapping();

// یادآوری نوبت‌های تأییدشده فردا؛ فقط در صورت تنظیم قالب پیامک فعال می‌شود.
Schedule::command('appointments:send-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping();

// ارسال یادآوری‌های سررسیدشده پیگیری بیماران؛ در صورت تنظیم قالب پیامک فعال می‌شود.
Schedule::command('patients:send-follow-up-reminders')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

// یادآوری اقساط نزدیک به سررسید و معوق، فقط با رضایت ثبت‌شده بیمار.
Schedule::command('finance:send-installment-reminders')
    ->dailyAt('09:15')
    ->withoutOverlapping();
