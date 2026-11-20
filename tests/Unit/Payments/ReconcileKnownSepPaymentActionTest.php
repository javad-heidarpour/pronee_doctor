<?php

declare(strict_types=1);

namespace Tests\Unit\Payments;

use App\Actions\Payments\CancelFailedAppointmentAction;
use App\Actions\Payments\ReconcileKnownSepPaymentAction;
use App\Actions\Payments\VerifyAndSettleSepPaymentAction;
use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Services\Payments\SepPaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class ReconcileKnownSepPaymentActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id(); $table->string('status'); $table->timestamps();
        });
        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('appointment_id'); $table->unsignedBigInteger('amount_rial');
            $table->string('status'); $table->string('payment_uuid')->unique(); $table->string('gateway_name');
            $table->string('ref_num')->nullable(); $table->timestamps();
        });
    }

    public function test_reconciliation_rejects_a_non_sep_gateway(): void
    {
        $payment = $this->payment('other', 'REF-1');
        $verify = Mockery::mock(VerifyAndSettleSepPaymentAction::class);
        $verify->shouldNotReceive('handle');
        $sep = Mockery::mock(SepPaymentService::class);

        $this->expectException(RuntimeException::class);
        (new ReconcileKnownSepPaymentAction($verify, app(CancelFailedAppointmentAction::class), $sep))->handle($payment);
    }

    public function test_reconciliation_requires_a_known_provider_reference(): void
    {
        $payment = $this->payment('sep', null);
        $verify = Mockery::mock(VerifyAndSettleSepPaymentAction::class);
        $verify->shouldNotReceive('handle');
        $sep = Mockery::mock(SepPaymentService::class);

        $this->expectException(RuntimeException::class);
        (new ReconcileKnownSepPaymentAction($verify, app(CancelFailedAppointmentAction::class), $sep))->handle($payment);
    }

    public function test_reconciliation_delegates_only_the_stored_reference_and_configured_terminal(): void
    {
        $payment = $this->payment('sep', 'REF-987');
        $verify = Mockery::mock(VerifyAndSettleSepPaymentAction::class);
        $verify->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::on(fn ($value) => $value instanceof AppointmentPayment && $value->id === $payment->id),
                Mockery::on(fn ($value) => $value instanceof Appointment && $value->id === $payment->appointment_id),
                'REF-987',
                '12345678',
                Mockery::type(SepPaymentService::class),
                Mockery::type(CancelFailedAppointmentAction::class)
            )
            ->andReturn(['status' => 'success']);
        $sep = Mockery::mock(SepPaymentService::class);
        config(['services.sep.terminal_id' => '12345678']);

        $result = (new ReconcileKnownSepPaymentAction($verify, app(CancelFailedAppointmentAction::class), $sep))->handle($payment);

        $this->assertSame(['status' => 'success'], $result);
    }

    private function payment(string $gateway, ?string $reference): AppointmentPayment
    {
        $appointmentId = Appointment::query()->create(['status' => 'pending'])->id;
        return AppointmentPayment::query()->create([
            'appointment_id' => $appointmentId,
            'amount_rial' => 7000000,
            'status' => 'initiated',
            'payment_uuid' => (string) Str::uuid(),
            'gateway_name' => $gateway,
            'ref_num' => $reference,
        ]);
    }
}
