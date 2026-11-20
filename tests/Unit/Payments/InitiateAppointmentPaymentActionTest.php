<?php

declare(strict_types=1);

namespace Tests\Unit\Payments;

use App\Actions\Payments\InitiateAppointmentPaymentAction;
use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Services\Payments\SepPaymentService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class InitiateAppointmentPaymentActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-17 08:00:00');

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->unsignedBigInteger('fee_rial');
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

    public function test_it_creates_a_payment_claim_and_saves_the_gateway_token(): void
    {
        $appointment = $this->appointment();
        config(['services.sep.callback_url' => 'https://proneex.ir/payment/callback']);

        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldReceive('requestToken')
            ->once()
            ->with(
                7000000,
                Mockery::type('string'),
                'https://proneex.ir/payment/callback',
                '09120000000'
            )
            ->andReturn([
                'token' => 'sep-token-123',
                'ipg_url' => 'https://sep.example/pay',
            ]);

        $token = app(InitiateAppointmentPaymentAction::class)->handle(
            $appointment,
            '09120000000',
            $sep
        );

        $this->assertSame('sep-token-123', $token);
        $this->assertDatabaseHas('appointment_payments', [
            'appointment_id' => $appointment->id,
            'amount_rial' => 7000000,
            'status' => 'initiated',
            'gateway_token' => 'sep-token-123',
            'gateway_name' => 'sep',
        ]);
    }

    public function test_it_falls_back_to_the_application_payment_callback_url_when_not_configured(): void
    {
        $appointment = $this->appointment();
        config(['services.sep.callback_url' => null]);
        $callbackUrl = route('payment.callback', [], true);

        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldReceive('requestToken')
            ->once()
            ->with(
                7000000,
                Mockery::type('string'),
                $callbackUrl,
                '09120000000'
            )
            ->andReturn(['token' => 'fallback-token']);

        $token = app(InitiateAppointmentPaymentAction::class)->handle(
            $appointment,
            '09120000000',
            $sep
        );

        $this->assertSame('fallback-token', $token);
    }

    public function test_it_reuses_an_existing_initiated_gateway_token(): void
    {
        $appointment = $this->appointment();
        $this->payment($appointment, 'initiated', 'existing-token');

        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldNotReceive('requestToken');

        $token = app(InitiateAppointmentPaymentAction::class)->handle(
            $appointment,
            '09120000000',
            $sep
        );

        $this->assertSame('existing-token', $token);
        $this->assertDatabaseCount('appointment_payments', 1);
    }

    public function test_it_returns_null_when_an_appointment_is_already_paid(): void
    {
        $appointment = $this->appointment();
        $this->payment($appointment, 'paid');

        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldNotReceive('requestToken');

        $result = app(InitiateAppointmentPaymentAction::class)->handle(
            $appointment,
            '09120000000',
            $sep
        );

        $this->assertNull($result);
    }

    public function test_it_rejects_an_expired_pending_reservation(): void
    {
        $appointment = $this->appointment(expiresAt: now()->subMinute());

        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldNotReceive('requestToken');

        $this->expectException(RuntimeException::class);

        app(InitiateAppointmentPaymentAction::class)->handle(
            $appointment,
            '09120000000',
            $sep
        );
    }

    public function test_it_does_not_start_a_new_attempt_when_manual_review_is_required(): void
    {
        $appointment = $this->appointment();
        $this->payment($appointment, 'initiated', null, 'REVIEW_REQUIRED');

        $sep = Mockery::mock(SepPaymentService::class);
        $sep->shouldNotReceive('requestToken');

        $this->expectException(RuntimeException::class);

        app(InitiateAppointmentPaymentAction::class)->handle(
            $appointment,
            '09120000000',
            $sep
        );
    }

    private function appointment(?\DateTimeInterface $expiresAt = null): Appointment
    {
        return Appointment::query()->create([
            'status' => 'pending',
            'fee_rial' => 7000000,
            'reservation_expires_at' => $expiresAt ?? now()->addMinutes(10),
        ]);
    }

    private function payment(
        Appointment $appointment,
        string $status,
        ?string $token = null,
        ?string $resultCode = null
    ): AppointmentPayment {
        return AppointmentPayment::query()->create([
            'appointment_id' => $appointment->id,
            'amount_rial' => 7000000,
            'status' => $status,
            'payment_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'gateway_token' => $token,
            'gateway_result_code' => $resultCode,
            'gateway_name' => 'sep',
        ]);
    }
}
