<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\MedicalCollaborationContractController;
use App\Models\Doctor;
use App\Models\MedicalCollaborationContract;
use App\Models\MedicalNetworkProvider;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

final class MedicalCollaborationContractControllerTest extends TestCase
{
    private User $doctorUser;
    private User $providerUser;
    private Doctor $doctor;
    private MedicalNetworkProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users',function(Blueprint $t):void{$t->id();$t->string('name');$t->string('family')->nullable();$t->string('email')->unique();$t->string('role');$t->boolean('is_active')->default(true);$t->timestamps();$t->softDeletes();});
        Schema::create('doctors',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('user_id');$t->string('slug')->nullable();$t->string('name')->nullable();$t->string('family')->nullable();$t->boolean('is_active')->default(true);$t->timestamps();$t->softDeletes();});
        Schema::create('addresses',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('doctor_id');$t->string('name');$t->boolean('is_active')->default(true);$t->timestamps();$t->softDeletes();});
        Schema::create('medical_network_providers',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('created_by_user_id');$t->unsignedBigInteger('organization_id')->nullable();$t->unsignedBigInteger('doctor_id')->nullable();$t->string('provider_type');$t->string('name');$t->string('slug');$t->string('city')->nullable();$t->string('address')->nullable();$t->string('phone')->nullable();$t->string('email')->nullable();$t->string('license_number')->nullable();$t->json('specialties')->nullable();$t->json('services')->nullable();$t->text('description')->nullable();$t->string('status');$t->boolean('is_verified');$t->decimal('rating',3,2)->default(0);$t->unsignedInteger('completed_referrals_count')->default(0);$t->timestamps();});
        Schema::create('medical_collaboration_contracts',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('doctor_id');$t->unsignedBigInteger('provider_id');$t->unsignedBigInteger('created_by_user_id');$t->string('service_type');$t->unsignedBigInteger('unit_fee_rial');$t->date('starts_at');$t->date('ends_at')->nullable();$t->string('status');$t->timestamp('accepted_at')->nullable();$t->text('terms')->nullable();$t->timestamps();});
        Schema::create('medical_referral_settlements',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('contract_id');$t->unsignedBigInteger('referral_id')->unique();$t->unsignedBigInteger('amount_rial');$t->string('status');$t->timestamp('paid_at')->nullable();$t->unsignedBigInteger('paid_by_user_id')->nullable();$t->timestamps();});
        $this->doctorUser=User::query()->create(['name'=>'Doctor','email'=>'contract-doctor@example.test','role'=>'doctor','is_active'=>true]);
        $this->providerUser=User::query()->create(['name'=>'Provider','email'=>'contract-provider@example.test','role'=>'user','is_active'=>true]);
        $this->doctor=Doctor::query()->create(['user_id'=>$this->doctorUser->id,'name'=>'Doctor','is_active'=>true]);
        $this->provider=MedicalNetworkProvider::query()->create([
            'created_by_user_id'=>$this->providerUser->id,'provider_type'=>'laboratory','name'=>'Lab Partner','slug'=>'lab-partner',
            'status'=>'active','is_verified'=>true,'specialties'=>[],'services'=>[],
        ]);
        Auth::guard('web')->setUser($this->doctorUser);
        session(['active_doctor_id'=>$this->doctor->id]);
    }

    public function test_contract_requires_provider_acceptance_before_becoming_active(): void
    {
        $audit=Mockery::mock(RecordSecurityAuditEventAction::class);$audit->shouldReceive('handle')->once();
        $controller=app(MedicalCollaborationContractController::class);
        $controller->store(Request::create('/portal/medical-network/contracts','POST',[
            'provider_id'=>$this->provider->id,'service_type'=>'laboratory','unit_fee_rial'=>850000,
            'starts_at'=>today()->toDateString(),'ends_at'=>today()->addMonths(3)->toDateString(),'terms'=>'تعهدات محرمانگی و زمان پاسخ',
        ]),$audit);
        $contract=MedicalCollaborationContract::query()->firstOrFail();
        $this->assertSame('proposed',$contract->status);

        $audit2=Mockery::mock(RecordSecurityAuditEventAction::class);$audit2->shouldReceive('handle')->once();
        Auth::guard('web')->setUser($this->providerUser);
        app(MedicalCollaborationContractController::class)->respond(Request::create('/portal/contracts/'.$contract->id.'/respond','POST',[
            'decision'=>'accept',
        ]),$contract,$audit2);

        $this->assertDatabaseHas('medical_collaboration_contracts',[
            'id'=>$contract->id,'status'=>'active',
        ]);
    }

    public function test_contract_cannot_be_proposed_for_a_mismatched_provider_service_type(): void
    {
        Auth::guard('web')->setUser($this->doctorUser);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(MedicalCollaborationContractController::class)->store(Request::create('/portal/medical-network/contracts','POST',[
            'provider_id'=>$this->provider->id,'service_type'=>'imaging','unit_fee_rial'=>850000,
            'starts_at'=>today()->toDateString(),
        ]),Mockery::mock(RecordSecurityAuditEventAction::class));
    }
}
