<?php

declare(strict_types=1);

namespace Tests\Unit\Payments;

use App\Services\Payments\SepPaymentService;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class SepPaymentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.sep.terminal_id' => '12345678']);
    }

    public function test_verify_uses_the_correct_sep_endpoint_and_expected_identifiers(): void
    {
        Http::fake([
            'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction' => Http::response([
                'Success' => true,
                'ResultCode' => 0,
                'TransactionDetail' => [
                    'RefNum' => 'SEP-REF-1',
                    'TerminalNumber' => '12345678',
                    'OrginalAmount' => 7000000,
                ],
            ], 200),
        ]);

        $result = app(SepPaymentService::class)->verifyTransaction('SEP-REF-1');

        $this->assertTrue($result['Success']);
        Http::assertSent(fn (ClientRequest $request): bool =>
            $request->url() === 'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction'
            && $request['TerminalNumber'] === '12345678'
            && $request['RefNum'] === 'SEP-REF-1'
        );
        Http::assertSentCount(1);
    }

    public function test_reverse_uses_the_documented_endpoint_and_does_not_retry(): void
    {
        Http::fake([
            'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/ReverseTransaction' => Http::response([
                'Success' => true,
                'ResultCode' => 0,
                'ResultDescription' => 'Reversed',
            ], 200),
        ]);

        $result = app(SepPaymentService::class)->reverseTransaction('SEP-REF-2');

        $this->assertSame(0, $result['ResultCode']);
        Http::assertSent(fn (ClientRequest $request): bool =>
            $request->url() === 'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/ReverseTransaction'
            && $request['TerminalNumber'] === '12345678'
            && $request['RefNum'] === 'SEP-REF-2'
        );
        Http::assertSentCount(1);
    }

    public function test_reverse_rejects_empty_reference_without_contacting_sep(): void
    {
        Http::fake();

        $this->expectException(RuntimeException::class);
        app(SepPaymentService::class)->reverseTransaction('');

        Http::assertNothingSent();
    }

    public function test_reverse_reports_transport_uncertainty_instead_of_claiming_failure(): void
    {
        Http::fake(['*' => Http::failedConnection()]);

        try {
            app(SepPaymentService::class)->reverseTransaction('SEP-REF-3');
            $this->fail('A connection failure must not be reported as a definite reversal failure.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('نامشخص', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }
}
