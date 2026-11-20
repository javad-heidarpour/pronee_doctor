<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\ClinicBusinessLeadController;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Response;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class ClinicBusinessLeadControllerTest extends TestCase
{
    private User $owner;
    private Doctor $doctor;
    private int $clinicId;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users',function(Blueprint $t):void{$t->id();$t->string('name');$t->string('email')->unique();$t->string('role');$t->boolean('is_active')->default(true);$t->timestamps();$t->softDeletes();});
        Schema::create('doctors',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('user_id');$t->string('slug')->nullable();$t->boolean('is_active')->default(true);$t->timestamps();$t->softDeletes();});
        Schema::create('addresses',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('doctor_id');$t->string('name');$t->boolean('is_active')->default(true);$t->timestamps();$t->softDeletes();});
        Schema::create('appointments',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('doctor_id');$t->unsignedBigInteger('clinic_id');$t->string('patient_phone');$t->timestamps();});
        Schema::create('clinic_marketing_campaigns',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('doctor_id');$t->string('name');$t->string('code');$t->timestamps();});
        Schema::create('clinic_business_leads',function(Blueprint $t):void{$t->id();$t->unsignedBigInteger('doctor_id');$t->unsignedBigInteger('clinic_id')->nullable();$t->unsignedBigInteger('campaign_id')->nullable();$t->unsignedBigInteger('created_by_user_id');$t->unsignedBigInteger('converted_appointment_id')->nullable();$t->string('name');$t->longText('phone');$t->longText('email')->nullable();$t->string('source');$t->string('status');$t->boolean('contact_consent');$t->timestamp('consent_at')->nullable();$t->timestamp('next_follow_up_at')->nullable();$t->text('notes')->nullable();$t->timestamp('converted_at')->nullable();$t->timestamps();});
        $this->owner=User::query()->create(['name'=>'Owner','email'=>'lead-owner@example.test','role'=>'doctor','is_active'=>true]);
        $this->doctor=Doctor::query()->create(['user_id'=>$this->owner->id,'is_active'=>true]);
        $this->clinicId=DB::table('addresses')->insertGetId(['doctor_id'=>$this->doctor->id,'name'=>'Clinic','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        Auth::guard('web')->setUser($this->owner);
        session(['active_doctor_id'=>$this->doctor->id]);
    }

    public function test_lead_requires_contact_consent_and_encrypts_phone_at_rest(): void
    {
        $audit=Mockery::mock(RecordSecurityAuditEventAction::class);$audit->shouldReceive('handle')->once();
        app(ClinicBusinessLeadController::class)->store(Request::create('/portal/marketing/leads','POST',[
            'name'=>'Interested Person','phone'=>'09121112222','clinic_id'=>$this->clinicId,
            'source'=>'website','contact_consent'=>'1','notes'=>'درخواست تماس درباره خدمات عمومی',
        ]),app(\App\Support\PatientIdentityNormalizer::class),$audit);

        $lead=\App\Models\ClinicBusinessLead::query()->firstOrFail();
        $this->assertSame('09121112222',$lead->phone);
        $this->assertNotSame('09121112222',DB::table('clinic_business_leads')->value('phone'));
        $this->assertTrue($lead->contact_consent);
        $this->assertSame('new',$lead->status);
    }

    public function test_lead_cannot_be_created_without_explicit_contact_consent(): void
    {
        $audit=Mockery::mock(RecordSecurityAuditEventAction::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(ClinicBusinessLeadController::class)->store(Request::create('/portal/marketing/leads','POST',[
            'name'=>'Interested Person','phone'=>'09121112222','source'=>'website',
        ]),app(\App\Support\PatientIdentityNormalizer::class),$audit);
    }

    public function test_lead_listing_masks_phone_and_conversion_requires_matching_real_appointment(): void
    {
        $leadId=DB::table('clinic_business_leads')->insertGetId([
            'doctor_id'=>$this->doctor->id,'clinic_id'=>$this->clinicId,'created_by_user_id'=>$this->owner->id,
            'name'=>'Interested Person','phone'=>encrypt('09121112222'),'source'=>'website','status'=>'qualified',
            'contact_consent'=>true,'consent_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        $response=app(ClinicBusinessLeadController::class)->index(Request::create('/portal/marketing/leads','GET'));
        $this->assertInstanceOf(Response::class,$response);
        $item=$response->toResponse(request())->getData(true)['page']['props']['leads']['data'][0];
        $this->assertSame('0912••••222',$item['phone_masked']);
        $this->assertArrayNotHasKey('phone',$item);

        $appointmentId=DB::table('appointments')->insertGetId([
            'doctor_id'=>$this->doctor->id,'clinic_id'=>$this->clinicId,'patient_phone'=>'09121112222',
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        $audit=Mockery::mock(RecordSecurityAuditEventAction::class);$audit->shouldReceive('handle')->once();
        app(ClinicBusinessLeadController::class)->update(Request::create('/portal/marketing/leads/'.$leadId,'PATCH',[
            'status'=>'converted','converted_appointment_id'=>$appointmentId,
        ]),\App\Models\ClinicBusinessLead::query()->findOrFail($leadId),$audit);
        $this->assertDatabaseHas('clinic_business_leads',['id'=>$leadId,'status'=>'converted','converted_appointment_id'=>$appointmentId]);
    }

    public function test_lead_cannot_be_converted_to_an_appointment_for_another_doctor(): void
    {
        $leadId=DB::table('clinic_business_leads')->insertGetId([
            'doctor_id'=>$this->doctor->id,'clinic_id'=>$this->clinicId,'created_by_user_id'=>$this->owner->id,
            'name'=>'Interested Person','phone'=>encrypt('09121112222'),'source'=>'website','status'=>'qualified',
            'contact_consent'=>true,'consent_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        $foreignAppointment=DB::table('appointments')->insertGetId([
            'doctor_id'=>$this->doctor->id+100,'clinic_id'=>$this->clinicId,'patient_phone'=>'09121112222',
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        $audit=Mockery::mock(RecordSecurityAuditEventAction::class);
        $this->expectException(HttpException::class);
        app(ClinicBusinessLeadController::class)->update(Request::create('/','PATCH',[
            'status'=>'converted','converted_appointment_id'=>$foreignAppointment,
        ]),\App\Models\ClinicBusinessLead::query()->findOrFail($leadId),$audit);
    }
}
