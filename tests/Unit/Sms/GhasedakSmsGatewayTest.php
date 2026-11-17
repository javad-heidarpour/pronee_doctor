<?php

namespace Tests\Unit\Sms;

use App\Services\Sms\GhasedakSmsGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class GhasedakSmsGatewayTest extends TestCase
{
    public function test_it_sends_template_messages_through_the_configured_gateway(): void
    {
        config(['services.ghasedak.api_key' => 'test-api-key']);

        Http::fake([
            'gateway.ghasedak.me/*' => Http::response(['IsSuccess' => true], 200),
        ]);

        $result = app(GhasedakSmsGateway::class)->sendTemplate(
            '09120000000',
            'DoctorRegister',
            [['param' => 'Code', 'value' => '123456']],
            'doctor-register-test'
        );

        $this->assertTrue($result['IsSuccess']);

        Http::assertSent(fn (Request $request): bool =>
            $request->hasHeader('ApiKey', 'test-api-key')
            && $request['templateName'] === 'DoctorRegister'
            && $request['receptors'][0]['mobile'] === '09120000000'
        );
    }

    public function test_it_rejects_missing_gateway_credentials(): void
    {
        config(['services.ghasedak.api_key' => null]);

        $this->expectException(RuntimeException::class);

        app(GhasedakSmsGateway::class)->sendTemplate(
            '09120000000',
            'DoctorRegister',
            [],
            'doctor-register-test'
        );
    }
}
