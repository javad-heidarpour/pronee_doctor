<?php
declare(strict_types=1);
namespace App\Services;
use Carbon\Carbon;
use Carbon\CarbonInterface;
final class ClinicalDecisionSupport
{
 public function evaluate(array $snapshot, ?CarbonInterface $now=null): array
 {
  $now=$now?Carbon::instance($now):now();
  $allergies=is_array($snapshot['allergies']??null)?$snapshot['allergies']:[];
  $medications=is_array($snapshot['medications']??null)?$snapshot['medications']:[];
  $vitals=is_array($snapshot['vitals']??null)?$snapshot['vitals']:[];
  $checks=[
   ['key'=>'allergies','title'=>'بازبینی حساسیت‌ها','status'=>$allergies?'available':'unknown','message'=>$allergies?count($allergies).' مورد حساسیت ثبت شده؛ نوع و واکنش با بیمار تطبیق داده شود.':'حساسیت فعالی ثبت نشده؛ وضعیت باید مستقیماً از بیمار پرسیده و تأیید شود.'],
   ['key'=>'medications','title'=>'تطبیق داروهای جاری','status'=>$medications?'available':'unknown','message'=>$medications?count($medications).' داروی فعال ثبت شده؛ مصرف واقعی و تغییرات بررسی شود.':'فهرست داروی فعالی موجود نیست؛ داروهای مصرفی بیمار اخذ و تطبیق داده شوند.']
  ];
  $latest=collect($vitals)->filter(fn($v)=>!empty($v['measured_at']))->sortByDesc(fn($v)=>Carbon::parse($v['measured_at'])->timestamp)->first();
  if(!$latest){$status='unknown';$message='اندازه‌گیری تاریخ‌دار در دسترس نیست؛ در صورت نیاز بالینی علائم حیاتی ثبت شود.';}
  else{$hours=max(0,(int)Carbon::parse($latest['measured_at'])->diffInHours($now));$status=$hours>24?'stale':'available';$message=$status==='stale'?'آخرین اندازه‌گیری بیش از ۲۴ ساعت قبل است؛ تازگی آن بررسی شود.':'آخرین اندازه‌گیری در ۲۴ ساعت اخیر است؛ تناسب آن با وضعیت فعلی بررسی شود.';}
  $checks[]=['key'=>'vitals','title'=>'تازگی علائم حیاتی','status'=>$status,'message'=>$message,'measured_at'=>$latest['measured_at']??null];
  $count=count(array_filter($checks,fn($c)=>$c['status']!=='available'));
  return ['version'=>1,'checks'=>$checks,'review_count'=>$count,'summary'=>$count?$count.' بخش نیازمند تکمیل یا بازبینی است.':'اطلاعات پایه موجود است؛ ارتباط بالینی همچنان باید بررسی شود.','limitations'=>['no_diagnosis','no_drug_interaction_check','no_dose_recommendation','no_automated_prescribing']];
 }
}