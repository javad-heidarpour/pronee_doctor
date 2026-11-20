<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\PatientInsuranceController;
use App\Models\Doctor;
use App\Models\Patient;
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

final class PatientInsuranceControllerTest extends TestCase
{
    private User $owner;
    private Doctor $doctor;
    private Patient $patient;
    private int $clinicId;
    private int $appointmentId;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('email')->unique();
            $table->string('role'); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('doctors', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('doctor_id'); $table->string('name');
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('patients', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('doctor_id'); $table->string('name');
            $table->string('phone')->nullable(); $table->string('national_code')->nullable(); $table->timestamps();
        });
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('doctor_id'); $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('clinic_id'); $table->date('appointment_date')->nullable();
            $table->time('appointment_time')->nullable(); $table->string('status')->default('confirmed'); $table->timestamps();
        });
        Schema::create('patient_insurance_verifications', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('doctor_id'); $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('appointment_id')->nullable(); $table->string('payer_code');
            $table->string('payer_name')->nullable(); $table->text('policy_number')->nullable();
            $table->text('member_number')->nullable(); $table->string('status'); $table->timestamp('checked_at')->nullable();
            $table->date('valid_from')->nullable(); $table->date('valid_until')->nullable();
            $table->string('tracking_id')->nullable(); $table->longText('response_payload')->nullable();
            $table->string('failure_code')->nullable(); $table->text('failure_message')->nullable(); $table->timestamps();
        });

        $this->owner = User::query()->create(['name'=>'Owner','email'=>'insurance-owner@example.test','role'=>'doctor','is_active'=>true]);
        $this->doctor = Doctor::query()->create(['user_id'=>$this->owner->id,'is_active'=>true]);
        $this->clinicId = DB::table('addresses')->insertGetId([
            'doctor_id'=>$this->doctor->id,'name'=>'Clinic','is_active'=>true,'created_at'=>now(),'updated_at'=>now(),
        ]);
        $this->patient = Patient::query()->create([
            'doctor_id'=>$this->doctor->id,'name'=>'Patient Example','phone'=>'09120000000',
        ]);
        $this->appointmentId = DB::table('appointments')->insertGetId([
            'doctor_id'=>$this->doctor->id,'patient_id'=>$this->patient->id,'clinic_id'=>$this->clinicId,
            'appointment_date'=>today()->toDateString(),'appointment_time'=>'09:00:00','status'=>'confirmed',
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        Auth::guard('web')->setUser($this->owner);
        session(['active_doctor_id'=>$this->doctor->id]);
    }

    public function test_manual_verification_is_saved_encrypted_and_audited_without_claiming_live_integration(): void
    {
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->once()->with('patient.insurance.verification_recorded', Mockery::type(\App\Models\PatientInsuranceVerification::class), Mockery::on(fn ($meta) => ($meta['status'] ?? null) === 'verified'));

        $response = app(PatientInsuranceController::class)->store(Request::create('/portal/patients/'.$this->patient->id.'/insurance', 'POST', [
            'appointment_id'=>$this->appointmentId,'payer_code'=>'tamin','payer_name'=>'تأمین اجتماعی',
            'policy_number'=>'POLICY-998877','member_number'=>'MEMBER-112233','status'=>'verified',
            'valid_from'=>'2026-01-01','valid_until'=>'2027-01-01','tracking_id'=>'MANUAL-1',
        ]), $this->patient, $audit);

        $this->assertSame('verified', DB::table('patient_insurance_verifications')->value('status'));
        $this->assertStringContainsString('دستی', session('success'));
        $record = \App\Models\PatientInsuranceVerification::query()->firstOrFail();
        $this->assertSame('POLICY-998877', $record->policy_number);
        $this->assertSame('manual', $record->response_payload['verification_source']);
    }

    public function test_verified_coverage_requires_policy_number_and_validity_end(): void
    {
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(PatientInsuranceController::class)->store(Request::create('/portal/patients/'.$this->patient->id.'/insurance', 'POST', [
            'payer_code'=>'tamin','payer_name'=>'تأمین اجتماعی','status'=>'verified',
        ]), $this->patient, $audit);
    }

    public function test_insurance_record_cannot_be_created_for_an_appointment_outside_patient_scope(): void
    {
        $foreignAppointment = DB::table('appointments')->insertGetId([
            'doctor_id'=>$this->doctor->id,'patient_id'=>$this->patient->id,'clinic_id'=>$this->clinicId + 100,
            'appointment_date'=>today()->toDateString(),'appointment_time'=>'10:00:00','status'=>'confirmed',
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $this->expectException(HttpException::class);
        app(PatientInsuranceController::class)->store(Request::create('/portal/patients/'.$this->patient->id.'/insurance', 'POST', [
            'appointment_id'=>$foreignAppointment,'payer_code'=>'tamin','payer_name'=>'تأمین اجتماعی','status'=>'pending',
        ]), $this->patient, $audit);
    }

    public function test_history_masks_policy_and_member_numbers(): void
    {
        DB::table('patient_insurance_verifications')->insert([
            'doctor_id'=>$this->doctor->id,'patient_id'=>$this->patient->id,'payer_code'=>'tamin',
            'payer_name'=>'تأمین اجتماعی','policy_number'=>encrypt('POLICY-998877'),
            'member_number'=>encrypt('MEMBER-112233'),'status'=>'verified','checked_at'=>now(),
            'created_at'=>now(),'updated_at'=>now(),
        ]);

        $response = app(PatientInsuranceController::class)->index($this->patient);
        $this->assertInstanceOf(Response::class, $response);
        $item = $response->getProps()['verifications']['data'][0];
        $this->assertArrayHasKey('policy_number_masked', $item);
        $this->assertArrayNotHasKey('policy_number', $item);
        $this->assertArrayNotHasKey('member_number', $item);
    }
}
