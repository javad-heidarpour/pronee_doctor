<?php
declare(strict_types=1);
namespace App\Services\PatientRetention;
use Carbon\CarbonInterface;
final class RetentionSegment
{
    /** Time-based operational review only; not a churn probability or diagnosis. */
    public function classify(?CarbonInterface $lastAppointment, bool $hasUpcomingAppointment, bool $hasOpenFollowUp, CarbonInterface $today, int $threshold = 90): array
    {
        $threshold = max(30, min(730, $threshold));
        if ($hasUpcomingAppointment) return ['key'=>'scheduled','label'=>'دارای نوبت آینده','priority'=>0,'days_inactive'=>$lastAppointment ? (int)$lastAppointment->diffInDays($today) : null];
        if ($hasOpenFollowUp) return ['key'=>'follow_up','label'=>'دارای پیگیری باز','priority'=>1,'days_inactive'=>$lastAppointment ? (int)$lastAppointment->diffInDays($today) : null];
        if (!$lastAppointment) return ['key'=>'unknown','label'=>'سابقه مراجعه کافی نیست','priority'=>2,'days_inactive'=>null];
        $days=max(0,(int)$lastAppointment->diffInDays($today));
        if ($days >= $threshold) return ['key'=>'win_back','label'=>'نیازمند بررسی بازگشت بیمار','priority'=>4,'days_inactive'=>$days];
        if ($days >= max(30,$threshold-30)) return ['key'=>'attention','label'=>'نیازمند بررسی ارتباط','priority'=>3,'days_inactive'=>$days];
        return ['key'=>'recent','label'=>'مراجعه نسبتاً اخیر','priority'=>1,'days_inactive'=>$days];
    }
}
