<?php

declare(strict_types=1);

namespace App\Services\Patient;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class PhysicianWorkflowAssistant
{
    public function suggest(array $summary, ?CarbonInterface $now = null): array
    {
        $now = $now ? Carbon::instance($now) : now();
        $tasks = [];
        $allergies = $summary['allergies'] ?? [];
        $medications = $summary['medications'] ?? [];
        $vitals = $summary['latest_vitals'] ?? [];
        $followUps = $summary['open_follow_ups'] ?? [];

        if ($allergies === []) {
            $tasks[] = [
                'key'=>'verify_allergies','priority'=>'high','title'=>'تطبیق وضعیت حساسیت‌ها',
                'reason'=>'در سوابق بارگذاری‌شده حساسیت فعالی دیده نمی‌شود؛ وضعیت باید مستقیماً از بیمار پرسیده و ثبت شود.',
            ];
        }
        if ($medications === []) {
            $tasks[] = [
                'key'=>'reconcile_medications','priority'=>'high','title'=>'تطبیق داروهای مصرفی',
                'reason'=>'فهرست داروی فعال در داده‌های بارگذاری‌شده موجود نیست؛ مصرف واقعی بیمار بازبینی شود.',
            ];
        }
        if ($vitals === []) {
            $tasks[] = [
                'key'=>'record_vitals','priority'=>'normal','title'=>'بررسی نیاز به ثبت علائم حیاتی',
                'reason'=>'اندازه‌گیری تاریخ‌دار در داده‌های بارگذاری‌شده وجود ندارد.',
            ];
        } else {
            $stale = collect($vitals)->filter(fn ($v) => !empty($v['measured_at']))
                ->every(fn ($v) => Carbon::parse($v['measured_at'])->lt($now->copy()->subHours(24)));
            if ($stale) {
                $tasks[] = [
                    'key'=>'refresh_vitals','priority'=>'normal','title'=>'بازبینی تازگی علائم حیاتی',
                    'reason'=>'تمام اندازه‌گیری‌های نمایش‌داده‌شده بیش از ۲۴ ساعت قدمت دارند؛ در صورت نیاز بالینی به‌روز شوند.',
                ];
            }
        }

        $overdue = collect($followUps)->filter(fn ($task) => !empty($task['due_at'])
            && Carbon::parse($task['due_at'])->lt($now))->count();
        if ($overdue > 0) {
            $tasks[] = [
                'key'=>'review_overdue_followups','priority'=>'high','title'=>'پیگیری وظایف مراقبتی سررسیدگذشته',
                'reason'=>$overdue.' وظیفه پیگیری در داده‌های بارگذاری‌شده از موعد عبور کرده است.',
            ];
        }

        return [
            'generated_at'=>$now->toIso8601String(),
            'mode'=>'transparent_rules',
            'tasks'=>$tasks,
            'disclaimer'=>'این دستیار فقط کارهای قابل بررسی را از داده‌های ثبت‌شده یادآوری می‌کند؛ تشخیص، تجویز، تعیین دوز یا تصمیم درمانی خودکار انجام نمی‌دهد.',
        ];
    }
}
