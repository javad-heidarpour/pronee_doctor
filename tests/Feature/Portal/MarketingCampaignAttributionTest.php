<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Http\Controllers\Portal\ClinicMarketingController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class MarketingCampaignAttributionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('doctors',function(Blueprint $t):void{$t->id();$t->string('slug');$t->boolean('is_active')->default(true);$t->timestamps();});
        Schema::create('clinic_marketing_campaigns',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('doctor_id');$t->unsignedBigInteger('clinic_id')->nullable();$t->string('name');$t->string('code')->unique();$t->string('channel');$t->string('status');$t->unsignedBigInteger('budget_rial')->nullable();$t->timestamp('starts_at')->nullable();$t->timestamp('ends_at')->nullable();$t->timestamps();});
        Schema::create('clinic_marketing_events',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('campaign_id');$t->unsignedBigInteger('appointment_id')->nullable();$t->string('event_type');$t->timestamp('occurred_at');$t->timestamps();});
    }

    public function test_active_campaign_track_records_a_click_and_sets_limited_attribution_context(): void
    {
        DB::table('doctors')->insert(['id'=>3,'slug'=>'doctor-three','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $campaignId=DB::table('clinic_marketing_campaigns')->insertGetId([
            'doctor_id'=>3,'name'=>'Social campaign','code'=>'ABC12345','channel'=>'social','status'=>'active',
            'starts_at'=>now()->subDay(),'ends_at'=>now()->addDay(),'created_at'=>now(),'updated_at'=>now(),
        ]);

        $response=app(ClinicMarketingController::class)->track('abc12345');

        $this->assertSame(1,DB::table('clinic_marketing_events')->where('campaign_id',$campaignId)->where('event_type','click')->count());
        $this->assertSame($campaignId,(int)session('proneex_marketing_campaign_id'));
        $this->assertSame(3,(int)session('proneex_marketing_doctor_id'));
        $this->assertGreaterThan(now()->addDays(29)->timestamp,(int)session('proneex_marketing_expires_at'));
    }

    public function test_paused_or_expired_campaign_cannot_be_tracked(): void
    {
        DB::table('doctors')->insert(['id'=>3,'slug'=>'doctor-three','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('clinic_marketing_campaigns')->insert([
            'doctor_id'=>3,'name'=>'Paused campaign','code'=>'PAUSED01','channel'=>'social','status'=>'paused',
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(ClinicMarketingController::class)->track('PAUSED01');
    }
}
