<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\ClinicProfessionalPartnershipController;
use App\Models\ClinicProfessionalPartnership;
use App\Models\Doctor;
use App\Models\MedicalNetworkProvider;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

final class ClinicProfessionalPartnershipControllerTest extends TestCase
{
    private User $doctorUser;
    private User $providerUser;
    private Doctor $doctor;
    private MedicalNetworkProvider $provider;
    private int $clinicId;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users',function(Blueprint $t):void{$t->id();$t->string('name');$t->string('family')->nullable();$t->string('email')->unique();$t->string('role');$t->boolean('is_active')->default(true);$t->timestamps();$t->softDeletes();});
        Schema::create('doctors',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('user_id');$t->string('slug')->nullable();$t->string('name')->nullable();$t->string('family')->nullable();$t->boolean('is_active')->default(true);$t->timestamps();$t->softDeletes();});
        Schema::create('addresses',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('doctor_id');$t->string('name');$t->boolean('is_active')->default(true);$t->timestamps();$t->softDeletes();});
        Schema::create('medical_network_providers',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('created_by_user_id');$t->unsignedBigInteger('organization_id')->nullable();$t->unsignedBigInteger('doctor_id')->nullable();$t->string('provider_type');$t->string('name');$t->string('slug');$t->string('city')->nullable();$t->string('address')->nullable();$t->string('phone')->nullable();$t->string('email')->nullable();$t->string('license_number')->nullable();$t->json('specialties')->nullable();$t->json('services')->nullable();$t->text('description')->nullable();$t->string('status');$t->boolean('is_verified');$t->decimal('rating',3,2)->default(0);$t->unsignedInteger('completed_referrals_count')->default(0);$t->timestamps();});
        Schema::create('clinic_professional_partnerships',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('doctor_id');$t->unsignedBigInteger('clinic_id');$t->unsignedBigInteger('provider_id');$t->unsignedBigInteger('created_by_user_id');$t->string('model');$t->string('service_name');$t->unsignedBigInteger('service_fee_rial')->default(0);$t->unsignedInteger('professional_share_bps')->default(0);$t->unsignedInteger('clinic_share_bps')->default(0);$t->unsignedBigInteger('fixed_fee_rial')->default(0);$t->unsignedBigInteger('periodic_fee_rial')->default(0);$t->string('periodic_cycle')->nullable();$t->string('payment_direction');$t->date('starts_at');$t->date('ends_at')->nullable();$t->string('status');$t->timestamp('accepted_at')->nullable();$t->text('terms')->nullable();$t->timestamps();});
        $this->doctorUser=User::query()->create(['name'=>'Clinic Owner','email'=>'partnership-owner@example.test','role'=>'doctor','is_active'=>true]);
        $this->providerUser=User::query()->create(['name'=>'Professional','email'=>'partnership-provider@example.test','role'=>'user','is_active'=>true]);
        $this->doctor=Doctor::query()->create(['user_id'=>$this->doctorUser->id,'name'=>'Clinic Owner','is_active'=>true]);
        $this->clinicId=DB::table('addresses')->insertGetId(['doctor_id'=>$this->doctor->id,'name'=>'Clinic','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $this->provider=MedicalNetworkProvider::query()->create([
            'created_by_user_id'=>$this->providerUser->id,'provider_type'=>'physiotherapist','name'=>'Physio Partner','slug'=>'physio-partner',
            'status'=>'active','is_verified'=>true,'specialties'=>[],'services'=>[],
        ]);
        Auth::guard('web')->setUser($this->doctorUser);
        session(['active_doctor_id'=>$this->doctor->id]);
    }

    public function test_partnership_proposal_activates_only_after_provider_acceptance(): void
    {
        $audit=Mockery::mock(RecordSecurityAuditEventAction::class);$audit->shouldReceive('handle')->once();
        $controller=app(ClinicProfessionalPartnershipController::class);
        $controller->store(Request::create('/portal/professional-partnerships','POST',[
            'clinic_id'=>$this->clinicId,'provider_id'=>$this->provider->id,'model'=>'percentage',
            'service_name'=>'فیزیوتراپی','service_fee_rial'=>1000000,'professional_share_percent'=>70,
            'payment_direction'=>'clinic_to_professional','starts_at'=>today()->toDateString(),
            'terms'=>'شرایط همکاری و محرمانگی',
        ]),$audit);
        $partnership=ClinicProfessionalPartnership::query()->firstOrFail();
        $this->assertSame('proposed',$partnership->status);
        $this->assertSame(7000,(int)$partnership->professional_share_bps);
        $this->assertSame(3000,(int)$partnership->clinic_share_bps);

        $audit2=Mockery::mock(RecordSecurityAuditEventAction::class);$audit2->shouldReceive('handle')->once();
        Auth::guard('web')->setUser($this->providerUser);
        app(ClinicProfessionalPartnershipController::class)->respond(Request::create('/portal/professional-partnerships/'.$partnership->id.'/respond','POST',[
            'decision'=>'accept',
        ]),$partnership,$audit2);

        $this->assertDatabaseHas('clinic_professional_partnerships',['id'=>$partnership->id,'status'=>'active']);
    }

    public function test_percentage_partnership_rejects_a_zero_share_for_either_party(): void
    {
        Auth::guard('web')->setUser($this->doctorUser);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(ClinicProfessionalPartnershipController::class)->store(Request::create('/portal/professional-partnerships','POST',[
            'clinic_id'=>$this->clinicId,'provider_id'=>$this->provider->id,'model'=>'percentage',
            'service_name'=>'فیزیوتراپی','service_fee_rial'=>1000000,'professional_share_percent'=>100,
            'payment_direction'=>'clinic_to_professional','starts_at'=>today()->toDateString(),
        ]),Mockery::mock(RecordSecurityAuditEventAction::class));
    }
}
