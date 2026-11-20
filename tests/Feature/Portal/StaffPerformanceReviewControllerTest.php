<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\StaffPerformanceReviewController;
use App\Models\ClinicOrganization;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class StaffPerformanceReviewControllerTest extends TestCase
{
    private User $owner;
    private User $staff;
    private Doctor $doctor;
    private ClinicOrganization $organization;
    private int $clinicId;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users',function(Blueprint $t):void{$t->id();$t->string('name');$t->string('family')->nullable();$t->string('email')->unique();$t->string('password')->nullable();$t->string('role');$t->boolean('is_active')->default(true);$t->timestamps();$t->softDeletes();});
        Schema::create('doctors',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('user_id');$t->string('slug')->nullable();$t->boolean('is_active')->default(true);$t->timestamps();$t->softDeletes();});
        Schema::create('doctor_user',function(Blueprint $t):void{$t->unsignedBigInteger('doctor_id');$t->unsignedBigInteger('user_id');$t->json('permissions')->nullable();$t->json('clinic_ids')->nullable();$t->boolean('managed_by_organization')->default(false);$t->timestamps();$t->primary(['doctor_id','user_id']);});
        Schema::create('addresses',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('doctor_id');$t->string('name');$t->boolean('is_active')->default(true);$t->timestamps();$t->softDeletes();});
        Schema::create('clinic_organizations',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('owner_user_id');$t->string('name');$t->string('slug');$t->string('timezone')->nullable();$t->string('status')->default('active');$t->json('settings')->nullable();$t->timestamps();});
        Schema::create('clinic_organization_doctors',function(Blueprint $t):void{$t->unsignedBigInteger('organization_id');$t->unsignedBigInteger('doctor_id');$t->string('role')->default('practitioner');$t->unsignedInteger('revenue_share_basis_points')->default(7000);$t->string('status')->default('active');$t->date('joined_at')->nullable();$t->timestamps();$t->primary(['organization_id','doctor_id']);});
        Schema::create('clinic_organization_clinics',function(Blueprint $t):void{$t->unsignedBigInteger('organization_id');$t->unsignedBigInteger('clinic_id');$t->string('branch_code')->nullable();$t->boolean('is_primary')->default(false);$t->string('status')->default('active');$t->timestamps();$t->primary(['organization_id','clinic_id']);});
        Schema::create('clinic_organization_members',function(Blueprint $t):void{$t->unsignedBigInteger('organization_id');$t->unsignedBigInteger('user_id');$t->string('role')->nullable();$t->text('permissions')->nullable();$t->text('clinic_ids')->nullable();$t->boolean('is_active')->default(true);$t->timestamps();$t->primary(['organization_id','user_id']);});
        Schema::create('clinic_staff_performance_reviews',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('organization_id');$t->unsignedBigInteger('clinic_id');$t->unsignedBigInteger('staff_user_id');$t->unsignedBigInteger('reviewer_user_id');$t->date('period_start');$t->date('period_end');$t->unsignedTinyInteger('reliability_score');$t->unsignedTinyInteger('task_completion_score');$t->unsignedTinyInteger('teamwork_score');$t->unsignedTinyInteger('communication_score');$t->unsignedTinyInteger('learning_score');$t->text('strengths')->nullable();$t->text('improvement_areas')->nullable();$t->text('goals')->nullable();$t->text('reviewer_notes')->nullable();$t->string('status');$t->timestamp('published_at')->nullable();$t->timestamp('acknowledged_at')->nullable();$t->text('employee_response')->nullable();$t->timestamps();});

        $this->owner=User::query()->create(['name'=>'Clinic Owner','email'=>'review-owner@example.test','role'=>'doctor','is_active'=>true]);
        $this->staff=User::query()->create(['name'=>'Staff Member','email'=>'review-staff@example.test','role'=>'secretary','is_active'=>true]);
        $this->doctor=Doctor::query()->create(['user_id'=>$this->owner->id,'slug'=>'review-owner','is_active'=>true]);
        DB::table('doctor_user')->insert(['doctor_id'=>$this->doctor->id,'user_id'=>$this->owner->id,'permissions'=>json_encode([]),'clinic_ids'=>json_encode([]),'created_at'=>now(),'updated_at'=>now()]);
        $this->clinicId=DB::table('addresses')->insertGetId(['doctor_id'=>$this->doctor->id,'name'=>'Clinic','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $this->organization=ClinicOrganization::query()->create(['owner_user_id'=>$this->owner->id,'name'=>'Organization','slug'=>'review-org','status'=>'active']);
        DB::table('clinic_organization_doctors')->insert(['organization_id'=>$this->organization->id,'doctor_id'=>$this->doctor->id,'role'=>'owner','revenue_share_basis_points'=>10000,'status'=>'active','joined_at'=>today()->toDateString(),'created_at'=>now(),'updated_at'=>now()]);
        DB::table('clinic_organization_clinics')->insert(['organization_id'=>$this->organization->id,'clinic_id'=>$this->clinicId,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        DB::table('clinic_organization_members')->insert(['organization_id'=>$this->organization->id,'user_id'=>$this->staff->id,'role'=>'receptionist','permissions'=>json_encode([]),'clinic_ids'=>json_encode([$this->clinicId]),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        Auth::guard('web')->setUser($this->owner);
        session(['active_doctor_id'=>$this->doctor->id]);
    }

    public function test_owner_can_create_publish_and_employee_can_acknowledge_a_review(): void
    {
        $controller=app(StaffPerformanceReviewController::class);
        $audit=Mockery::mock(RecordSecurityAuditEventAction::class);$audit->shouldReceive('handle')->once();
        $controller->store(Request::create('/portal/staff-performance','POST',[
            'organization_id'=>$this->organization->id,'clinic_id'=>$this->clinicId,'staff_user_id'=>$this->staff->id,
            'period_start'=>'2026-10-01','period_end'=>'2026-10-31','reliability_score'=>4,
            'task_completion_score'=>5,'teamwork_score'=>4,'communication_score'=>3,'learning_score'=>4,
            'strengths'=>'پاسخ‌گویی مناسب و رعایت فرایندها','improvement_areas'=>'تقویت مستندسازی',
            'goals'=>'تکمیل آموزش سامانه','reviewer_notes'=>'جلسه بازخورد برگزار شد',
        ]),$audit);
        $reviewId=(int)DB::table('clinic_staff_performance_reviews')->value('id');
        $this->assertDatabaseHas('clinic_staff_performance_reviews',['id'=>$reviewId,'status'=>'draft']);

        $audit2=Mockery::mock(RecordSecurityAuditEventAction::class);$audit2->shouldReceive('handle')->once();
        $controller->publish($reviewId,$audit2);
        $this->assertDatabaseHas('clinic_staff_performance_reviews',['id'=>$reviewId,'status'=>'published']);

        Auth::guard('web')->setUser($this->staff);
        $audit3=Mockery::mock(RecordSecurityAuditEventAction::class);$audit3->shouldReceive('handle')->once();
        $controller->acknowledge(Request::create('/portal/staff-performance/'.$reviewId.'/acknowledge','PATCH',[
            'employee_response'=>'ارزیابی را دریافت کردم و درباره هدف آموزشی گفت‌وگو می‌کنم.',
        ]),$reviewId,$audit3);
        $this->assertDatabaseHas('clinic_staff_performance_reviews',[
            'id'=>$reviewId,'status'=>'published','employee_response'=>'ارزیابی را دریافت کردم و درباره هدف آموزشی گفت‌وگو می‌کنم.',
        ]);
        $this->assertNotNull(DB::table('clinic_staff_performance_reviews')->where('id',$reviewId)->value('acknowledged_at'));
    }

    public function test_employee_cannot_create_or_publish_another_persons_review(): void
    {
        Auth::guard('web')->setUser($this->staff);
        $this->expectException(HttpException::class);
        app(StaffPerformanceReviewController::class)->store(Request::create('/portal/staff-performance','POST',[
            'organization_id'=>$this->organization->id,'clinic_id'=>$this->clinicId,'staff_user_id'=>$this->staff->id,
            'period_start'=>'2026-10-01','period_end'=>'2026-10-31','reliability_score'=>4,
            'task_completion_score'=>4,'teamwork_score'=>4,'communication_score'=>4,'learning_score'=>4,
        ]),Mockery::mock(RecordSecurityAuditEventAction::class));
    }
}
