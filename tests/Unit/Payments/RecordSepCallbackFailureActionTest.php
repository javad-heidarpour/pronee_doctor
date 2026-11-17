<?php

declare(strict_types=1);

namespace Tests\Unit\Payments;

use App\Actions\Payments\RecordSepCallbackFailureAction;
use App\Models\Appointment;
use App\Models\AppointmentPayment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

final class RecordSepCallbackFailureActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->string('status');
            $table->string('gateway_result_code')->nullable();
            $table->text('gateway_result_message')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_marks_an_unresolved_callback_as_requiring_manual_review(): void
    {
        $appointment = Appointment::query()->create(['status' => 'pending']);
        $payment = AppointmentPayment::query()->create([
            'appointment_id' => $appointment->id,
            'status' => 'initiated',
        ]);

        app(RecordSepCallbackFailureAction::class)->handle(
            $payment,
            'payment-uuid',
            'gateway-ref',
            new RuntimeException('Gateway response could not be verified.')
        );

        $this->assertDatabaseHas('appointment_payments', [
            'id' => $payment->id,
            'status' => 'initiated',
            'gateway_result_code' => 'REVIEW_REQUIRED',
            'gateway_result_message' => 'خطا در بررسی تراکنش؛ وضعیت نیازمند بررسی است.',
        ]);
    }

    public function test_it_does_not_overwrite_a_successfully_paid_payment(): void
    {
        $appointment = Appointment::query()->create(['status' => 'confirmed']);
        $payment = AppointmentPayment::query()->create([
            'appointment_id' => $appointment->id,
            'status' => 'paid',
            'gateway_result_code' => '0',
            'gateway_result_message' => 'Verified',
        ]);

        app(RecordSepCallbackFailureAction::class)->handle(
            $payment,
            'payment-uuid',
            'gateway-ref',
            new RuntimeException('A late callback exception.')
        );

        $this->assertDatabaseHas('appointment_payments', [
            'id' => $payment->id,
            'status' => 'paid',
            'gateway_result_code' => '0',
            'gateway_result_message' => 'Verified',
        ]);
    }

    public function test_it_can_record_a_callback_failure_without_a_matched_payment(): void
    {
        app(RecordSepCallbackFailureAction::class)->handle(
            null,
            'unknown-payment-uuid',
            'unknown-ref',
            new RuntimeException('Payment lookup failed.')
        );

        $this->assertDatabaseCount('appointment_payments', 0);
    }
}
