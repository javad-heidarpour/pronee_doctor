<?php

declare(strict_types=1);

namespace Tests\Unit\Payments;

use App\Actions\Payments\CancelFailedAppointmentAction;
use App\Actions\Payments\VerifyAndSettleSepPaymentAction;
use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Services\Payments\SepPaymentService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

final class VerifyAndSettleSepPaymentActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-17 08:00:00');

        Schema::create('addresses', function (Blueprint $table): void { $table->id(); $table->unsignedBigInteger('organization_id')->nullable(); $table->timestamps(); });
        Schema::create('clinic_financial_accounts', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('organization_id')->nullable(); $table->unsignedBigInteger('clinic_id')->nullable();
            $table->string('code'); $table->string('scope_key')->unique(); $table->string('name'); $table->string('type');
            $table->boolean('is_system')->default(true); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('clinic_journal_entries', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('organization_id')->nullable(); $table->unsignedBigInteger('clinic_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable(); $table->unsignedBigInteger('created_by')->nullable();
            $table->string('source_type')->nullable(); $table->unsignedBigInteger('source_id')->nullable(); $table->string('reference')->nullable();
            $table->string('description'); $table->dateTime('occurred_at'); $table->string('status')->default('posted');
            $table->string('idempotency_key')->nullable()->unique(); $table->timestamps();
        });
        Schema::create('clinic_journal_lines', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('journal_entry_id'); $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('debit')->default(0); $table->unsignedBigInteger('credit')->default(0); $table->string('memo')->nullable(); $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('time_slot_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('patient_name');
            $table->string('patient_phone');
            $table->string('patient_national_code')->nullable();
            $table->string('status');
            $table->string('payment_status')->nullable();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->unsignedBigInteger('fee_rial')->default(0);
            $table->timestamp('reservation_expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('amount_rial');
            $table->string('status', 20)->default('pending');
            $table->string('payment_uuid', 36)->unique();
            $table->string('gateway_token')->nullable();
            $table->string('ref_num')->nullable();
            $table->string('terminal_id')->nullable();
            $table->string('gateway_name', 50)->default('sep');
            $table->text('gateway_result_message')->nullable();
            $table->string('gateway_result_code', 50)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('gateway_verified_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_atomically_settles_a_verified_payment_and_persists_the_gateway_reference(): void
    {
        $appointment = $this->appointment();
        $payment = $this->payment($appointment);

        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldReceive('verifyTransaction')
            ->once()
            ->with('123456789')
            ->andReturn($this->verifiedResponse());

        $cancel = Mockery::mock(CancelFailedAppointmentAction::class);
        $cancel->shouldNotReceive('handle');

        $result = app(VerifyAndSettleSepPaymentAction::class)->handle(
            $payment,
            $appointment,
            '123456789',
            '12345678',
            $sep,
            $cancel
        );

        $this->assertSame(['status' => 'success'], $result);
        $this->assertDatabaseHas('appointment_payments', [
            'id' => $payment->id,
            'status' => 'paid',
            'ref_num' => '123456789',
            'terminal_id' => '12345678',
            'gateway_result_code' => '0',
        ]);
        $this->assertNotNull($payment->fresh()->paid_at);
        $this->assertNotNull($payment->fresh()->gateway_verified_at);
        $this->assertDatabaseHas('clinic_journal_entries', [
            'idempotency_key' => 'appointment-payment:' . $payment->id,
            'source_type' => 'appointment_payment', 'status' => 'posted',
        ]);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'reservation_expires_at' => null,
        ]);
    }

    public function test_it_does_not_settle_when_the_verified_amount_does_not_match(): void
    {
        $appointment = $this->appointment();
        $payment = $this->payment($appointment);

        $response = $this->verifiedResponse();
        $response['TransactionDetail']['OrginalAmount'] = 6000000;

        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldReceive('verifyTransaction')->once()->andReturn($response);

        $cancel = Mockery::mock(CancelFailedAppointmentAction::class);
        $cancel->shouldNotReceive('handle');

        $result = app(VerifyAndSettleSepPaymentAction::class)->handle(
            $payment,
            $appointment,
            '123456789',
            '12345678',
            $sep,
            $cancel
        );

        $this->assertSame('amount_mismatch', $result['status']);
        $this->assertDatabaseHas('appointment_payments', [
            'id' => $payment->id,
            'status' => 'initiated',
            'ref_num' => null,
        ]);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'pending',
        ]);
    }

    public function test_it_does_not_settle_when_the_verified_terminal_does_not_match(): void
    {
        $appointment = $this->appointment();
        $payment = $this->payment($appointment);

        $response = $this->verifiedResponse();
        $response['TransactionDetail']['TerminalNumber'] = '87654321';

        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldReceive('verifyTransaction')->once()->andReturn($response);

        $cancel = Mockery::mock(CancelFailedAppointmentAction::class);
        $cancel->shouldNotReceive('handle');

        $result = app(VerifyAndSettleSepPaymentAction::class)->handle(
            $payment,
            $appointment,
            '123456789',
            '12345678',
            $sep,
            $cancel
        );

        $this->assertSame('verification_mismatch', $result['status']);
        $this->assertDatabaseHas('appointment_payments', [
            'id' => $payment->id,
            'status' => 'initiated',
            'ref_num' => null,
        ]);
    }

    public function test_it_delegates_failed_gateway_verification_to_cancellation_action(): void
    {
        $appointment = $this->appointment();
        $payment = $this->payment($appointment);

        $response = $this->verifiedResponse();
        $response['ResultCode'] = 1;

        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldReceive('verifyTransaction')->once()->andReturn($response);

        $cancel = Mockery::mock(CancelFailedAppointmentAction::class);
        $cancel->shouldReceive('handle')->once()->with($payment)->andReturn('cancelled');

        $result = app(VerifyAndSettleSepPaymentAction::class)->handle(
            $payment,
            $appointment,
            '123456789',
            '12345678',
            $sep,
            $cancel
        );

        $this->assertSame('failed', $result['status']);
        $this->assertDatabaseHas('appointment_payments', [
            'id' => $payment->id,
            'status' => 'initiated',
        ]);
    }

    public function test_it_returns_already_paid_without_contacting_the_gateway(): void
    {
        $appointment = $this->appointment();
        $payment = $this->payment($appointment, 'paid');

        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldNotReceive('verifyTransaction');

        $cancel = Mockery::mock(CancelFailedAppointmentAction::class);
        $cancel->shouldNotReceive('handle');

        $result = app(VerifyAndSettleSepPaymentAction::class)->handle(
            $payment,
            $appointment,
            '123456789',
            '12345678',
            $sep,
            $cancel
        );

        $this->assertSame('already_paid', $result['status']);
    }

    private function appointment(): Appointment
    {
        DB::table('addresses')->insertOrIgnore(['id'=>1,'organization_id'=>9,'created_at'=>now(),'updated_at'=>now()]);
        return Appointment::query()->create([
            'doctor_id' => 1,
            'clinic_id' => 1,
            'time_slot_id' => 1,
            'user_id' => null,
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
            'status' => 'pending',
            'appointment_date' => '2026-11-18',
            'appointment_time' => '09:00:00',
            'fee_rial' => 7000000,
            'reservation_expires_at' => now()->addMinutes(10),
        ]);
    }

    private function payment(Appointment $appointment, string $status = 'initiated'): AppointmentPayment
    {
        return AppointmentPayment::query()->create([
            'appointment_id' => $appointment->id,
            'amount_rial' => 7000000,
            'status' => $status,
            'payment_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'gateway_name' => 'sep',
        ]);
    }

    private function verifiedResponse(): array
    {
        return [
            'Success' => true,
            'ResultCode' => 0,
            'ResultDescription' => 'Verified',
            'TransactionDetail' => [
                'OrginalAmount' => 7000000,
                'RefNum' => '123456789',
                'TerminalNumber' => '12345678',
            ],
        ];
    }
}
