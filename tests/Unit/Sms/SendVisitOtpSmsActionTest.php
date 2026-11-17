<?php

declare(strict_types=1);

namespace Tests\Unit\Sms;

use App\Actions\Sms\SendVisitOtpSmsAction;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SendVisitOtpSmsActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-17 08:00:00');
        config([
            'cache.default' => 'array',
            'services.ghasedak.api_key' => 'test-api-key',
        ]);
        Cache::flush();

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->string('patient_phone');
            $table->string('status');
            $table->timestamp('reservation_expires_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_sends_otp_only_for_an_active_unexpired_reservation(): void
    {
        Http::fake([
            'gateway.ghasedak.me/*' => Http::response(['IsSuccess' => true], 200),
        ]);

        $appointment = $this->appointment();
        session()->put(
            'sms.visit.allowed.' . $appointment->id,
            now()->addMinutes(20)->timestamp
        );

        $response = app(SendVisitOtpSmsAction::class)->handle($appointment->id);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
        $this->assertFalse(session()->has('sms.visit.allowed.' . $appointment->id));

        Http::assertSent(fn (Request $request): bool =>
            $request->hasHeader('ApiKey', 'test-api-key')
            && $request['templateName'] === 'Visit'
            && $request['receptors'][0]['mobile'] === '09120000000'
        );
    }

    public function test_it_rejects_an_expired_reservation_even_if_the_session_token_is_valid(): void
    {
        Http::fake();

        $appointment = $this->appointment(
            expiresAt: now()->subMinute()
        );

        session()->put(
            'sms.visit.allowed.' . $appointment->id,
            now()->addMinutes(20)->timestamp
        );

        $response = app(SendVisitOtpSmsAction::class)->handle($appointment->id);

        $this->assertSame(422, $response->getStatusCode());
        Http::assertNothingSent();
    }

    public function test_it_rejects_non_pending_appointments(): void
    {
        Http::fake();

        $appointment = $this->appointment(status: 'confirmed');

        session()->put(
            'sms.visit.allowed.' . $appointment->id,
            now()->addMinutes(20)->timestamp
        );

        $response = app(SendVisitOtpSmsAction::class)->handle($appointment->id);

        $this->assertSame(422, $response->getStatusCode());
        Http::assertNothingSent();
    }

    private function appointment(
        string $status = 'pending',
        ?\DateTimeInterface $expiresAt = null
    ): Appointment {
        return Appointment::query()->create([
            'patient_phone' => '09120000000',
            'status' => $status,
            'reservation_expires_at' => $expiresAt ?? now()->addMinutes(10),
        ]);
    }
}
