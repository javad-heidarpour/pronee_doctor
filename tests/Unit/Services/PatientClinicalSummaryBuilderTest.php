<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Patient\PatientClinicalSummaryBuilder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class PatientClinicalSummaryBuilderTest extends TestCase
{
    public function test_it_builds_a_source_faithful_summary_from_loaded_patient_records(): void
    {
        $builder = new PatientClinicalSummaryBuilder();
        $summary = $builder->build(
            'Patient',
            collect([(object)['created_at'=>Carbon::parse('2026-11-19'),'diagnosis'=>'ثبت‌شده','assessment'=>'ارزیابی ثبت‌شده','plan'=>'برنامه ثبت‌شده','status'=>'signed']]),
            collect([(object)['status'=>'active','title'=>'فشار خون','severity'=>'moderate','onset_date'=>Carbon::parse('2025-01-01')]]),
            collect([
                (object)['kind'=>'allergy','is_active'=>true,'title'=>'پنی‌سیلین','description'=>'واکنش ثبت‌شده'],
                (object)['kind'=>'medication','is_active'=>true,'title'=>'دارو A','dosage'=>'یک قرص','frequency'=>'روزانه','start_date'=>null,'end_date'=>null],
            ]),
            collect([(object)['measurement_type'=>'blood_pressure','value'=>'120/80','unit'=>'mmHg','measured_at'=>Carbon::parse('2026-11-20 09:00:00')]]),
            collect([(object)['status'=>'pending','title'=>'پیگیری آزمایش','due_at'=>Carbon::parse('2026-11-22'),'priority'=>'normal']]),
            collect([(object)['status'=>'active','title'=>'مسیر مراقبت','goal'=>'کنترل','steps'=>collect([(object)['status'=>'verified'],(object)['status'=>'pending']])]])
        );

        $this->assertSame('Patient',$summary['patient_name']);
        $this->assertSame('ثبت‌شده',$summary['latest_encounter']['diagnosis']);
        $this->assertSame('پنی‌سیلین',$summary['allergies'][0]['title']);
        $this->assertSame('دارو A',$summary['medications'][0]['title']);
        $this->assertSame('blood_pressure',$summary['latest_vitals'][0]['type']);
        $this->assertSame(1,$summary['active_pathways'][0]['steps_verified']);
        $this->assertContains('missing_data_does_not_mean_normal',$summary['limitations']);
    }

    public function test_empty_records_are_reported_as_missing_not_as_normal(): void
    {
        $summary = app(PatientClinicalSummaryBuilder::class)->build(
            'Patient',collect(),collect(),collect(),collect(),collect(),collect()
        );

        $this->assertNull($summary['latest_encounter']);
        $this->assertCount(0,$summary['allergies']);
        $this->assertCount(0,$summary['medications']);
        $this->assertContains('missing_data_does_not_mean_normal',$summary['limitations']);
    }
}
