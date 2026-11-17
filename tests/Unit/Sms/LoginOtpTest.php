<?php

namespace Tests\Unit\Sms;

use App\Services\LoginOtp;
use App\Contracts\Sms\SmsGateway;
use Illuminate\Support\Facades\Cache;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class LoginOtpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::flush();
    }

    public function test_otp_is_one_time_and_cannot_be_reused(): void
    {
        $phone = '09120000000';
        $code = null;

        $gateway = Mockery::mock(SmsGateway::class);
        $gateway->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(function ($target, $template, $inputs, $reference) use ($phone, &$code): bool {
                $code = $inputs[0]['value'] ?? null;

                return $target === $phone
                    && $template === 'DoctorRegister'
                    && is_string($code)
                    && strlen($code) === 6
                    && $reference === 'doctor-register-' . $phone;
            })
            ->andReturn([]);

        $this->app->instance(SmsGateway::class, $gateway);

        $otp = app(LoginOtp::class);
        $otp->send($phone);

        $this->assertTrue($otp->verify($phone, $code));
        $this->assertFalse($otp->verify($phone, $code));
    }

    public function test_otp_resend_is_rate_limited(): void
    {
        $gateway = Mockery::mock(SmsGateway::class);
        $gateway->shouldReceive('sendTemplate')->once()->andReturn([]);

        $this->app->instance(SmsGateway::class, $gateway);

        $otp = app(LoginOtp::class);
        $otp->send('09120000001');

        $this->expectException(RuntimeException::class);
        $otp->send('09120000001');
    }
}
