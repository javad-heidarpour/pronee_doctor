<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Actions\Payments\ReverseAppointmentPaymentAction;
use App\Models\AppointmentPayment;
use App\Models\AppointmentPaymentRefund;
use App\Services\Finance\ClinicAccountingService;
use App\Services\Payments\SepPaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class ReverseAppointmentPaymentActionTest extends TestCase
{
    private AppointmentPayment $payment;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.sep.terminal_id' => '123456']);

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->date('appointment_date')->nullable();
            $table->time('appointment_time')->nullable();
            $table->string('status')->default('confirmed');
            $table->timestamps();
        });
        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('amount_rial');
            $table->string('status');
            $table->string('payment_uuid')->nullable();
            $table->string('gateway_token')->nullable();
            $table->string('ref_num')->nullable();
            $table->string('terminal_id')->nullable();
            $table->string('gateway_name')->default('sep');
            $table->text('gateway_result_message')->nullable();
            $table->string('gateway_result_code')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('gateway_verified_at')->nullable();
            $table->timestamps();
        });
        Schema::create('appointment_payment_refunds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_payment_id')->unique();
            $table->unsignedBigInteger('requested_by_user_id');
            $table->unsignedBigInteger('processed_by_user_id')->nullable();
            $table->unsignedBigInteger('amount_rial');
            $table->text('reason');
            $table->string('status');
            $table->string('gateway_ref_num');
            $table->string('gateway_terminal_id');
            $table->string('gateway_result_code')->nullable();
            $table->text('gateway_result_message')->nullable();
            $table->longText('gateway_response')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamp('requested_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        $appointmentId = DB::table('appointments')->insertGetId([
            'doctor_id' => 7, 'clinic_id' => 11, 'appointment_date' => today()->toDateString(),
            'appointment_time' => '09:00:00', 'status' => 'confirmed',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->payment = AppointmentPayment::query()->create([
            'appointment_id' => $appointmentId,
            'amount_rial' => 1250000,
            'status' => 'paid',
            'payment_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'ref_num' => 'REF-123',
            'terminal_id' => '123456',
            'gateway_name' => 'sep',
            'paid_at' => now(),
        ]);
    }

    public function test_successful_reverse_marks_payment_refunded_and_posts_one_ledger_reversal(): void
    {
        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldReceive('reverseTransaction')->once()->with('REF-123')
            ->andReturn(['ResultCode' => 0, 'ResultDescription' => 'Reversed']);
        $accounting = Mockery::mock(ClinicAccountingService::class);
        $accounting->shouldReceive('reverseAppointmentPayment')->once()
            ->with(null, 11, 7, 4, $this->payment->id, Mockery::type('int'), 1250000, Mockery::type('string'), Mockery::type('string'));

        $result = app(ReverseAppointmentPaymentAction::class)->handle(
            $this->payment, 4, 'پرداخت اشتباه ثبت شده است.', $sep, $accounting
        );

        $this->assertSame('reversed', $result['status']);
        $this->assertSame('refunded', $this->payment->fresh()->status);
        $this->assertDatabaseHas('appointment_payment_refunds', [
            'id' => $result['refund_id'], 'status' => 'reversed',
            'gateway_result_code' => '0', 'amount_rial' => 1250000,
        ]);
    }

    public function test_gateway_rejection_does_not_change_the_paid_payment(): void
    {
        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldReceive('reverseTransaction')->once()->andReturn([
            'ResultCode' => 12, 'ResultDescription' => 'Not reversible',
        ]);
        $accounting = Mockery::mock(ClinicAccountingService::class);
        $accounting->shouldNotReceive('reverseAppointmentPayment');

        $result = app(ReverseAppointmentPaymentAction::class)->handle(
            $this->payment, 4, 'درخواست مستند برگشت وجه', $sep, $accounting
        );

        $this->assertSame('rejected', $result['status']);
        $this->assertSame('paid', $this->payment->fresh()->status);
        $this->assertDatabaseHas('appointment_payment_refunds', [
            'id' => $result['refund_id'], 'status' => 'rejected',
        ]);
    }

    public function test_uncertain_gateway_outcome_is_persisted_and_never_retried_automatically(): void
    {
        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldReceive('reverseTransaction')->once()->andThrow(new RuntimeException('timeout'));
        $accounting = Mockery::mock(ClinicAccountingService::class);
        $accounting->shouldNotReceive('reverseAppointmentPayment');
        $action = app(ReverseAppointmentPaymentAction::class);

        $result = $action->handle($this->payment, 4, 'درخواست برگشت با علت معتبر', $sep, $accounting);
        $this->assertSame('unknown', $result['status']);
        $this->assertDatabaseHas('appointment_payment_refunds', [
            'id' => $result['refund_id'], 'status' => 'unknown',
        ]);

        try {
            $action->handle($this->payment, 4, 'تلاش مجدد نباید انجام شود', $sep, $accounting);
            $this->fail('An uncertain reverse must not be retried automatically.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('نامشخص', $exception->getMessage());
        }
    }

    public function test_terminal_mismatch_is_rejected_before_calling_the_gateway(): void
    {
        $this->payment->update(['terminal_id' => '999999']);
        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldNotReceive('reverseTransaction');
        $accounting = Mockery::mock(ClinicAccountingService::class);

        $this->expectException(RuntimeException::class);
        app(ReverseAppointmentPaymentAction::class)->handle(
            $this->payment, 4, 'شناسه ترمینال با پذیرنده مغایرت دارد.', $sep, $accounting
        );
    }

    public function test_a_refund_record_prevents_a_second_gateway_request_even_when_rejected(): void
    {
        AppointmentPaymentRefund::query()->create([
            'appointment_payment_id' => $this->payment->id,
            'requested_by_user_id' => 4,
            'amount_rial' => 1250000,
            'reason' => 'درخواست قبلی توسط درگاه رد شد.',
            'status' => 'rejected',
            'gateway_ref_num' => 'REF-123',
            'gateway_terminal_id' => '123456',
            'idempotency_key' => hash('sha256', 'sep-payment-refund:' . $this->payment->id),
            'requested_at' => now(),
        ]);
        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldNotReceive('reverseTransaction');
        $accounting = Mockery::mock(ClinicAccountingService::class);

        $this->expectException(RuntimeException::class);
        app(ReverseAppointmentPaymentAction::class)->handle(
            $this->payment, 4, 'ارسال مجدد درخواست برگشت وجه', $sep, $accounting
        );
    }

    public function test_only_a_verified_paid_sep_payment_can_be_reversed(): void
    {
        $this->payment->update(['status' => 'pending', 'paid_at' => null]);
        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldNotReceive('reverseTransaction');
        $accounting = Mockery::mock(ClinicAccountingService::class);

        $this->expectException(RuntimeException::class);
        app(ReverseAppointmentPaymentAction::class)->handle(
            $this->payment, 4, 'درخواست معتبر برای برگشت وجه', $sep, $accounting
        );
    }
}
