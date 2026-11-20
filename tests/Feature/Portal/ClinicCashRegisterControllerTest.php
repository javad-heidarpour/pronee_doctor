<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\ClinicCashRegisterController;
use App\Models\ClinicCashRegisterSession;
use App\Models\ClinicJournalEntry;
use App\Models\Doctor;
use App\Models\User;
use App\Services\Finance\ClinicAccountingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class ClinicCashRegisterControllerTest extends TestCase
{
    private User $owner;
    private Doctor $doctor;
    private int $clinicId;

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
        Schema::create('clinic_cash_register_sessions', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('doctor_id'); $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('opened_by_user_id'); $table->unsignedBigInteger('closed_by_user_id')->nullable();
            $table->unsignedBigInteger('opening_balance_rial'); $table->unsignedBigInteger('closing_count_rial')->nullable();
            $table->bigInteger('difference_rial')->nullable(); $table->string('status'); $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable(); $table->text('opening_note')->nullable();
            $table->text('closing_note')->nullable(); $table->timestamps();
        });
        Schema::create('clinic_cash_transactions', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('session_id'); $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id'); $table->unsignedBigInteger('created_by_user_id');
            $table->string('direction'); $table->string('category'); $table->unsignedBigInteger('amount_rial');
            $table->string('description'); $table->string('idempotency_key')->unique(); $table->timestamps();
        });

        $this->owner = User::query()->create(['name'=>'Owner','email'=>'cash-owner@example.test','role'=>'doctor','is_active'=>true]);
        $this->doctor = Doctor::query()->create(['user_id'=>$this->owner->id,'is_active'=>true]);
        $this->clinicId = DB::table('addresses')->insertGetId([
            'doctor_id'=>$this->doctor->id,'name'=>'Main clinic','is_active'=>true,'created_at'=>now(),'updated_at'=>now(),
        ]);
        Auth::guard('web')->setUser($this->owner);
        session(['active_doctor_id'=>$this->doctor->id]);
    }

    public function test_opening_a_second_cash_session_for_the_same_clinic_is_rejected(): void
    {
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->once();
        $controller = app(ClinicCashRegisterController::class);
        $controller->open(Request::create('/portal/finance/cash-register/open','POST',[
            'clinic_id'=>$this->clinicId,'opening_balance_rial'=>500000,'opening_note'=>'شروع شیفت صبح',
        ]), $audit);

        $this->assertDatabaseHas('clinic_cash_register_sessions', [
            'clinic_id'=>$this->clinicId,'status'=>'open','opening_balance_rial'=>500000,
        ]);

        $this->expectException(HttpException::class);
        $controller->open(Request::create('/portal/finance/cash-register/open','POST',[
            'clinic_id'=>$this->clinicId,'opening_balance_rial'=>0,
        ]), $audit);
    }

    public function test_receipt_is_idempotent_and_posts_ledger_once(): void
    {
        $session = ClinicCashRegisterSession::query()->create([
            'doctor_id'=>$this->doctor->id,'clinic_id'=>$this->clinicId,'opened_by_user_id'=>$this->owner->id,
            'opening_balance_rial'=>100000,'status'=>'open','opened_at'=>now(),
        ]);
        $accounting = Mockery::mock(ClinicAccountingService::class);
        $accounting->shouldReceive('recordCashReceipt')->once()->andReturn(new ClinicJournalEntry());
        $accounting->shouldNotReceive('recordCashDisbursement');
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->twice();
        $request = Request::create('/portal/finance/cash-register/'.$session->id.'/transactions','POST',[
            'direction'=>'in','category'=>'service_receipt','amount_rial'=>250000,
            'description'=>'دریافت ویزیت حضوری','idempotency_key'=>'cash-receipt-idempotency-001',
        ]);
        $controller = app(ClinicCashRegisterController::class);
        $controller->store($request,$session,$accounting,$audit);
        $controller->store($request,$session,$accounting,$audit);

        $this->assertSame(1, DB::table('clinic_cash_transactions')->count());
        $this->assertDatabaseHas('clinic_cash_transactions',['amount_rial'=>250000,'direction'=>'in']);

        $this->expectException(HttpException::class);
        $controller->store(Request::create('/portal/finance/cash-register/'.$session->id.'/transactions','POST',[
            'direction'=>'in','category'=>'other_receipt','amount_rial'=>250000,
            'description'=>'دریافت ویزیت حضوری','idempotency_key'=>'cash-receipt-idempotency-001',
        ]),$session,$accounting,$audit);
    }

    public function test_close_calculates_and_persists_cash_difference(): void
    {
        $session = ClinicCashRegisterSession::query()->create([
            'doctor_id'=>$this->doctor->id,'clinic_id'=>$this->clinicId,'opened_by_user_id'=>$this->owner->id,
            'opening_balance_rial'=>100000,'status'=>'open','opened_at'=>now(),
        ]);
        DB::table('clinic_cash_transactions')->insert([
            ['session_id'=>$session->id,'doctor_id'=>$this->doctor->id,'clinic_id'=>$this->clinicId,'created_by_user_id'=>$this->owner->id,'direction'=>'in','category'=>'service_receipt','amount_rial'=>250000,'description'=>'receipt','idempotency_key'=>'close-test-in','created_at'=>now(),'updated_at'=>now()],
            ['session_id'=>$session->id,'doctor_id'=>$this->doctor->id,'clinic_id'=>$this->clinicId,'created_by_user_id'=>$this->owner->id,'direction'=>'out','category'=>'operating_expense','amount_rial'=>50000,'description'=>'expense','idempotency_key'=>'close-test-out','created_at'=>now(),'updated_at'=>now()],
        ]);
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->once();

        app(ClinicCashRegisterController::class)->close(Request::create('/portal/finance/cash-register/'.$session->id.'/close','POST',[
            'closing_count_rial'=>320000,'closing_note'=>'شمارش پایان شیفت',
        ]),$session,$audit);

        $this->assertDatabaseHas('clinic_cash_register_sessions',[
            'id'=>$session->id,'status'=>'closed','closing_count_rial'=>320000,'difference_rial'=>20000,
        ]);
    }
}
