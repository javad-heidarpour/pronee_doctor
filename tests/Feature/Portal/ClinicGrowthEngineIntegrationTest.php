<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Services\Growth\ClinicGrowthEngine;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ClinicGrowthEngineIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('appointments',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('doctor_id');$t->unsignedBigInteger('clinic_id');$t->unsignedBigInteger('patient_id')->nullable();$t->unsignedBigInteger('time_slot_id')->nullable();$t->date('appointment_date');$t->string('status');$t->timestamps();});
        Schema::create('time_slots',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('doctor_id');$t->unsignedBigInteger('clinic_id');$t->date('date');$t->string('status');$t->timestamps();});
        Schema::create('appointment_payments',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('appointment_id');$t->unsignedBigInteger('amount_rial');$t->string('status');$t->timestamp('paid_at')->nullable();$t->timestamps();});
        Schema::create('addresses',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('doctor_id');$t->string('name');$t->timestamps();});
        Schema::create('clinic_marketing_campaigns',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('doctor_id');$t->unsignedBigInteger('clinic_id')->nullable();$t->string('name');$t->string('code');$t->string('channel');$t->string('status');$t->unsignedBigInteger('budget_rial')->nullable();$t->timestamps();});
        Schema::create('clinic_marketing_events',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('campaign_id');$t->unsignedBigInteger('appointment_id')->nullable();$t->string('event_type');$t->timestamp('occurred_at');$t->timestamps();});
    }

    public function test_growth_report_scopes_clinic_and_period_and_returns_aggregate_only_data(): void
    {
        $doctorId=5;$clinicA=10;$clinicB=11;
        DB::table('addresses')->insert([
            ['id'=>$clinicA,'doctor_id'=>$doctorId,'name'=>'Clinic A','created_at'=>now(),'updated_at'=>now()],
            ['id'=>$clinicB,'doctor_id'=>$doctorId,'name'=>'Clinic B','created_at'=>now(),'updated_at'=>now()],
        ]);
        DB::table('appointments')->insert([
            ['id'=>1,'doctor_id'=>$doctorId,'clinic_id'=>$clinicA,'patient_id'=>100,'time_slot_id'=>1,'appointment_date'=>'2026-11-01','status'=>'visited','created_at'=>now(),'updated_at'=>now()],
            ['id'=>2,'doctor_id'=>$doctorId,'clinic_id'=>$clinicA,'patient_id'=>101,'time_slot_id'=>2,'appointment_date'=>'2026-11-10','status'=>'cancelled','created_at'=>now(),'updated_at'=>now()],
            ['id'=>3,'doctor_id'=>$doctorId,'clinic_id'=>$clinicB,'patient_id'=>102,'time_slot_id'=>3,'appointment_date'=>'2026-11-10','status'=>'visited','created_at'=>now(),'updated_at'=>now()],
        ]);
        DB::table('appointments')->insert([
            ['id'=>4,'doctor_id'=>$doctorId,'clinic_id'=>$clinicA,'patient_id'=>100,'time_slot_id'=>4,'appointment_date'=>'2026-10-01','status'=>'visited','created_at'=>now(),'updated_at'=>now()],
        ]);
        DB::table('time_slots')->insert([
            ['id'=>1,'doctor_id'=>$doctorId,'clinic_id'=>$clinicA,'date'=>'2026-11-01','status'=>'booked','created_at'=>now(),'updated_at'=>now()],
            ['id'=>2,'doctor_id'=>$doctorId,'clinic_id'=>$clinicA,'date'=>'2026-11-10','status'=>'available','created_at'=>now(),'updated_at'=>now()],
            ['id'=>3,'doctor_id'=>$doctorId,'clinic_id'=>$clinicB,'date'=>'2026-11-10','status'=>'booked','created_at'=>now(),'updated_at'=>now()],
        ]);
        DB::table('appointment_payments')->insert([
            ['appointment_id'=>1,'amount_rial'=>500000,'status'=>'paid','paid_at'=>'2026-11-02 10:00:00','created_at'=>now(),'updated_at'=>now()],
            ['appointment_id'=>2,'amount_rial'=>900000,'status'=>'failed','paid_at'=>null,'created_at'=>now(),'updated_at'=>now()],
        ]);
        $campaignId=DB::table('clinic_marketing_campaigns')->insertGetId([
            'doctor_id'=>$doctorId,'clinic_id'=>$clinicA,'name'=>'November','code'=>'NOV','channel'=>'social','status'=>'active','budget_rial'=>1000000,'created_at'=>now(),'updated_at'=>now(),
        ]);
        DB::table('clinic_marketing_events')->insert([
            ['campaign_id'=>$campaignId,'appointment_id'=>null,'event_type'=>'click','occurred_at'=>'2026-11-03 10:00:00','created_at'=>now(),'updated_at'=>now()],
            ['campaign_id'=>$campaignId,'appointment_id'=>1,'event_type'=>'booking','occurred_at'=>'2026-11-04 10:00:00','created_at'=>now(),'updated_at'=>now()],
            ['campaign_id'=>$campaignId,'appointment_id'=>null,'event_type'=>'click','occurred_at'=>'2026-10-03 10:00:00','created_at'=>now(),'updated_at'=>now()],
        ]);

        $report=app(ClinicGrowthEngine::class)->analyze($doctorId,[$clinicA,$clinicB],'2026-11-01','2026-11-30',$clinicA);

        $this->assertSame(2,$report['appointments']['total']);
        $this->assertSame(1,$report['appointments']['visited']);
        $this->assertSame(1,$report['appointments']['cancelled']);
        $this->assertSame(2,$report['patients']['active']);
        $this->assertSame(1,$report['patients']['new']);
        $this->assertSame(1,$report['patients']['returning']);
        $this->assertSame(500000,$report['finance']['paid_revenue_rial']);
        $this->assertSame(1,$report['marketing']['clicks']);
        $this->assertSame(1,$report['marketing']['bookings']);
        $this->assertArrayNotHasKey('patients',$report['marketing']['campaigns'][0]);
        $this->assertArrayNotHasKey('patient_name',$report['marketing']['campaigns'][0]);
    }
}
