<?php
declare(strict_types=1);
namespace App\Services\Scheduling;
use Carbon\CarbonImmutable;
final class ClinicCapacityAdvisor
{
    private const ACTIVE = ['pending', 'contacted', 'confirmed', 'visited', 'completed'];
    private const INACTIVE_APPOINTMENTS = ['cancelled', 'canceled', 'failed'];
    public function summarize(iterable $slots): array
    {
        $daily = $weekdays = [];
        $totals = ['capacity'=>0,'occupied'=>0,'available'=>0,'cancelled'=>0,'duration_minutes'=>0];
        foreach ($slots as $slot) {
            $date=(string)$slot['date']; $clinicId=(int)$slot['clinic_id'];
            $key=$clinicId.':'.$date; $status=(string)$slot['status'];
            $appointmentStatus=$slot['appointment_status'] ?? null;
            if (!isset($daily[$key])) $daily[$key]=[
                'clinic_id'=>$clinicId,'clinic_name'=>(string)($slot['clinic_name'] ?? ''),
                'date'=>$date,'capacity'=>0,'occupied'=>0,'available'=>0,'cancelled'=>0,
                'utilization_percent'=>0,'duration_minutes'=>0,
            ];
            if ($status==='cancelled') { $daily[$key]['cancelled']++; $totals['cancelled']++; continue; }
            $duration=$this->durationMinutes((string)$slot['start_time'],(string)$slot['end_time']);
            $occupied=!in_array($appointmentStatus,self::INACTIVE_APPOINTMENTS,true)
                && ($status==='booked' || in_array($appointmentStatus,self::ACTIVE,true));
            $daily[$key]['capacity']++; $daily[$key]['duration_minutes'] += $duration;
            $totals['capacity']++; $totals['duration_minutes'] += $duration;
            if ($occupied) { $daily[$key]['occupied']++; $totals['occupied']++; }
            else { $daily[$key]['available']++; $totals['available']++; }
            $weekday=(CarbonImmutable::parse($date)->dayOfWeekIso+1)%7;
            $weekdayKey=$clinicId.':'.$weekday;
            if (!isset($weekdays[$weekdayKey])) $weekdays[$weekdayKey]=[
                'clinic_id'=>$clinicId,'clinic_name'=>(string)($slot['clinic_name'] ?? ''),
                'day_of_week'=>$weekday,'observed_days'=>0,'observed_dates'=>[],'capacity'=>0,'occupied'=>0,'available'=>0,
            ];
            $weekdays[$weekdayKey]['observed_dates'][$date]=true;
            $weekdays[$weekdayKey]['capacity']++;
            if ($occupied) $weekdays[$weekdayKey]['occupied']++; else $weekdays[$weekdayKey]['available']++;
        }
        foreach ($daily as &$day) $day['utilization_percent']=$day['capacity']>0?(int)round($day['occupied']/$day['capacity']*100):0;
        unset($day);
        foreach ($weekdays as &$weekday) {
            $weekday['observed_days']=count($weekday['observed_dates']);
            unset($weekday['observed_dates']);
            $weekday['utilization_percent']=$weekday['capacity']>0?(int)round($weekday['occupied']/$weekday['capacity']*100):0;
            $weekday['signal']=match(true) {
                $weekday['capacity']<4 || $weekday['observed_days']<2=>'insufficient',
                $weekday['utilization_percent']>=80=>'high_demand',
                $weekday['utilization_percent']<=30=>'underused',
                default=>'balanced',
            };
            $weekday['recommendation']=match($weekday['signal']) {
                'insufficient'=>'داده کافی برای پیشنهاد تغییر ظرفیت وجود ندارد.',
                'high_demand'=>'تقاضا بالا است؛ افزایش محدود ظرفیت در همین روز هفته را بررسی کنید.',
                'underused'=>'ظرفیت کم‌استفاده است؛ الگوی ساعات کاری و تعداد نوبت‌ها را بازبینی کنید.',
                default=>'ظرفیت فعلی متعادل است؛ فعلاً تغییری پیشنهاد نمی‌شود.',
            };
        }
        unset($weekday);
        usort($daily,static fn($a,$b)=>[$a['date'],$a['clinic_id']]<=>[$b['date'],$b['clinic_id']]);
        usort($weekdays,static fn($a,$b)=>[$a['clinic_id'],$a['day_of_week']]<=>[$b['clinic_id'],$b['day_of_week']]);
        $totals['utilization_percent']=$totals['capacity']>0?(int)round($totals['occupied']/$totals['capacity']*100):0;
        return ['summary'=>$totals,'daily'=>array_values($daily),'weekday_recommendations'=>array_values($weekdays)];
    }
    private function durationMinutes(string $start,string $end): int
    {
        return max(0,(int)((CarbonImmutable::parse($end)->getTimestamp()-CarbonImmutable::parse($start)->getTimestamp())/60));
    }
}