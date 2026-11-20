<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\ClinicSharedServiceController;
use App\Models\ClinicOrganization;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

final class ClinicSharedServiceControllerTest extends TestCase
{
    private User $ownerA;
    private User $ownerB;
    private Doctor $doctorA;
    private Doctor $doctorB;
    private ClinicOrganization $orgA;
    private ClinicOrganization $orgB;
    private int $clinicA;
    private int $clinicB;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('email')->unique();
            $table->string('role'); $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('doctors', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('doctor_id'); $table->string('name');
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('clinic_organizations', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('owner_user_id'); $table->string('name'); $table->string('slug');
            $table->string('status')->default('active'); $table->string('timezone')->nullable(); $table->json('settings')->nullable(); $table->timestamps();
        });
        Schema::create('clinic_organization_doctors', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id'); $table->unsignedBigInteger('doctor_id'); $table->string('status');
            $table->timestamps(); $table->primary(['organization_id','doctor_id']);
        });
        Schema::create('clinic_organization_clinics', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id'); $table->unsignedBigInteger('clinic_id'); $table->string('status');
            $table->timestamps(); $table->primary(['organization_id','clinic_id']);
        });
        Schema::create('clinic_shared_services', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('organization_id'); $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id'); $table->unsignedBigInteger('created_by_user_id');
            $table->string('title'); $table->string('category'); $table->text('description');
            $table->unsignedBigInteger('price_rial'); $table->unsignedSmallInteger('weekly_capacity');
            $table->string('status'); $table->date('available_from')->nullable(); $table->date('available_until')->nullable(); $table->timestamps();
        });
        Schema::create('clinic_shared_service_requests', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('shared_service_id'); $table->unsignedBigInteger('requesting_organization_id');
            $table->unsignedBigInteger('requesting_doctor_id'); $table->unsignedBigInteger('requesting_clinic_id');
            $table->unsignedBigInteger('requested_by_user_id'); $table->unsignedBigInteger('responded_by_user_id')->nullable();
            $table->unsignedSmallInteger('requested_capacity'); $table->string('status');
            $table->text('request_note')->nullable(); $table->text('response_note')->nullable();
            $table->timestamp('responded_at')->nullable(); $table->timestamps();
        });

        $this->ownerA = User::query()->create(['name'=>'Owner A','email'=>'shared-a@example.test','role'=>'doctor','is_active'=>true]);
        $this->ownerB = User::query()->create(['name'=>'Owner B','email'=>'shared-b@example.test','role'=>'doctor','is_active'=>true]);
        $this->doctorA = Doctor::query()->create(['user_id'=>$this->ownerA->id,'is_active'=>true]);
        $this->doctorB = Doctor::query()->create(['user_id'=>$this->ownerB->id,'is_active'=>true]);
        $this->clinicA = DB::table('addresses')->insertGetId(['doctor_id'=>$this->doctorA->id,'name'=>'A clinic','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $this->clinicB = DB::table('addresses')->insertGetId(['doctor_id'=>$this->doctorB->id,'name'=>'B clinic','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $this->orgA = ClinicOrganization::query()->create(['owner_user_id'=>$this->ownerA->id,'name'=>'Org A','slug'=>'org-a','status'=>'active']);
        $this->orgB = ClinicOrganization::query()->create(['owner_user_id'=>$this->ownerB->id,'name'=>'Org B','slug'=>'org-b','status'=>'active']);
        foreach ([[$this->orgA,$this->doctorA,$this->clinicA],[$this->orgB,$this->doctorB,$this->clinicB]] as [$org,$doctor,$clinic]) {
            DB::table('clinic_organization_doctors')->insert(['organization_id'=>$org->id,'doctor_id'=>$doctor->id,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
            DB::table('clinic_organization_clinics')->insert(['organization_id'=>$org->id,'clinic_id'=>$clinic,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        }
    }

    public function test_service_can_be_published_requested_and_accepted_across_organizations(): void
    {
        $controller = app(ClinicSharedServiceController::class);
        $auditA = Mockery::mock(RecordSecurityAuditEventAction::class);
        $auditA->shouldReceive('handle')->once();
        Auth::guard('web')->setUser($this->ownerA);
        session(['active_doctor_id'=>$this->doctorA->id]);
        $controller->store(Request::create('/portal/shared-services','POST',[
            'organization_id'=>$this->orgA->id,'clinic_id'=>$this->clinicA,
            'title'=>'Ultrasound service','category'=>'imaging','description'=>'Ultrasound capacity available for referrals.',
            'price_rial'=>1200000,'weekly_capacity'=>12,
        ]),$auditA);
        $serviceId = (int) DB::table('clinic_shared_services')->value('id');

        $auditB = Mockery::mock(RecordSecurityAuditEventAction::class);
        $auditB->shouldReceive('handle')->once();
        Auth::guard('web')->setUser($this->ownerB);
        session(['active_doctor_id'=>$this->doctorB->id]);
        $controller->requestService(Request::create('/portal/shared-services/'.$serviceId.'/requests','POST',[
            'organization_id'=>$this->orgB->id,'clinic_id'=>$this->clinicB,
            'requested_capacity'=>3,'request_note'=>'درخواست ظرفیت هفتگی',
        ]),\App\Models\ClinicSharedService::query()->findOrFail($serviceId),$auditB);
        $requestId = (int) DB::table('clinic_shared_service_requests')->value('id');
        $this->assertDatabaseHas('clinic_shared_service_requests',['id'=>$requestId,'status'=>'pending']);

        $auditA2 = Mockery::mock(RecordSecurityAuditEventAction::class);
        $auditA2->shouldReceive('handle')->once();
        Auth::guard('web')->setUser($this->ownerA);
        session(['active_doctor_id'=>$this->doctorA->id]);
        $controller->respond(Request::create('/portal/shared-services/requests/'.$requestId.'/respond','PATCH',[
            'decision'=>'accept','response_note'=>'ظرفیت تأیید شد.',
        ]),\App\Models\ClinicSharedServiceRequest::query()->findOrFail($requestId),$auditA2);

        $this->assertDatabaseHas('clinic_shared_service_requests',[
            'id'=>$requestId,'status'=>'accepted','responded_by_user_id'=>$this->ownerA->id,
        ]);
    }

    public function test_provider_cannot_request_its_own_shared_service(): void
    {
        $serviceId = DB::table('clinic_shared_services')->insertGetId([
            'organization_id'=>$this->orgA->id,'doctor_id'=>$this->doctorA->id,'clinic_id'=>$this->clinicA,
            'created_by_user_id'=>$this->ownerA->id,'title'=>'Own service','category'=>'lab','description'=>'Own service description',
            'price_rial'=>1000,'weekly_capacity'=>1,'status'=>'published','created_at'=>now(),'updated_at'=>now(),
        ]);
        Auth::guard('web')->setUser($this->ownerA);
        session(['active_doctor_id'=>$this->doctorA->id]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(ClinicSharedServiceController::class)->requestService(Request::create('/','POST',[
            'organization_id'=>$this->orgA->id,'clinic_id'=>$this->clinicA,'requested_capacity'=>1,
        ]),\App\Models\ClinicSharedService::query()->findOrFail($serviceId),Mockery::mock(RecordSecurityAuditEventAction::class));
    }
}
