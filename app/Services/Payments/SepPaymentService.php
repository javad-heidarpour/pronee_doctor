<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Illuminate\Support\Facades\Log;




class SepPaymentService
{
    private string $gatewayUrl =
        'https://sep.shaparak.ir/OnlinePG/OnlinePG';

    private string $terminalId;

    public function __construct()
    {
        $this->terminalId = (string) config('services.sep.terminal_id');

        if ($this->terminalId === '') {
            throw new RuntimeException(
                'SEP Terminal ID is not configured.'
            );
        }
    }

    /**
     * Request a payment token from SEP.
     *
     * Amount must be in the unit required by your SEP contract.
     */

    public function requestToken(
        int $amount,
        string $resNum,
        string $redirectUrl,
        ?string $cellNumber = null
    ): array {
        if ($amount < 1) {
            throw new RuntimeException('مبلغ پرداخت معتبر نیست.');
        }

        $payload = [
            'action' => 'token',
            'TerminalId' => $this->terminalId,
            'RedirectUrl' => $redirectUrl,
            'ResNum' => $resNum,
            'Amount' => $amount,
        ];

        if ($cellNumber !== null && $cellNumber !== '') {
            // SEP در نمونه مستندات، شماره را بدون صفر ابتدایی می‌فرستد.
            $payload['CellNumber'] = preg_replace('/^0/', '', $cellNumber);
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->connectTimeout(10)
                ->timeout(30)
                ->post(
                    'https://sep.shaparak.ir/OnlinePG/OnlinePG',
                    $payload
                );

            $result = $response->json();

            // ثبت پاسخ برای تشخیص علت خطا؛ اطلاعات حساس ثبت نشود.
            if (!$response->successful()) {
                Log::error('SEP token HTTP error', [
                    'http_status' => $response->status(),
                    'response_type' => get_debug_type($result),
                    'response_keys' => is_array($result)
                        ? array_keys($result)
                        : [],
                    'res_num_fingerprint' => hash('sha256', $resNum),
                ]);

                throw new RuntimeException(
                    'خطای HTTP هنگام دریافت توکن از SEP.'
                );
            }

            if (!is_array($result)) {
                Log::error('SEP token response is not valid JSON', [
                    'response_type' => 'non_json',
                    'res_num_fingerprint' => hash('sha256', $resNum),
                ]);

                throw new RuntimeException(
                    'پاسخ درگاه SEP قابل پردازش نیست.'
                );
            }

            if (
                (int) ($result['status'] ?? 0) !== 1 ||
                empty($result['token'])
            ) {
                Log::error('SEP rejected token request', [
                    'status' => $result['status'] ?? null,
                    'error_code' => $result['errorCode'] ?? null,
                    'res_num_fingerprint' => hash('sha256', $resNum),
                ]);

                throw new RuntimeException(
                    'خطای SEP: ' .
                    ($result['errorDesc'] ?? 'توکن پرداخت صادر نشد.') .
                    (
                        isset($result['errorCode'])
                        ? ' (کد: ' . $result['errorCode'] . ')'
                        : ''
                    )
                );
            }

            $token = (string) $result['token'];

            return [
                'token' => $token,
                'ipg_url' =>
                    'https://sep.shaparak.ir/OnlinePG/SendToken?token=' .
                    urlencode($token),
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('SEP connection error', [
                'res_num_fingerprint' => hash('sha256', $resNum),
            ]);

            throw new RuntimeException(
                'ارتباط با سرور سامان‌کیش برقرار نشد.',
                0,
                $e
            );
        }
    }

    /**
     * URL to which the customer is redirected for payment.
     */
    public function gatewayUrl(): string
    {
        return $this->gatewayUrl;
    }
    /**
     * استعلام و تأیید تراکنش در سامان‌کیش
     */

    public function verifyTransaction(string $refNum): array
    {
        $terminalId = trim(
            (string) config('services.sep.terminal_id', '')
        );

        if ($terminalId === '' || trim($refNum) === '') {
            throw new \RuntimeException(
                'اطلاعات استعلام تراکنش ناقص است.'
            );
        }

        $response = Http::acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->post(
                'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTranscation',
                [
                    'TerminalNumber' => $terminalId,
                    'RefNum' => $refNum,
                ]
            );

        if (!$response->successful()) {
            Log::error('SEP verification HTTP error', [
                'http_status' => $response->status(),
                'ref_num_fingerprint' => hash('sha256', $refNum),
            ]);

            throw new \RuntimeException(
                'ارتباط با سرویس استعلام سامان‌کیش ناموفق بود.'
            );
        }

        $result = $response->json();

        if (
            !is_array($result) ||
            !array_key_exists('ResultCode', $result)
        ) {
            Log::error('SEP invalid verification response', [
                'ref_num_fingerprint' => hash('sha256', $refNum),
                'response_type' => get_debug_type($result),
            ]);

            throw new \RuntimeException(
                'پاسخ استعلام سامان‌کیش معتبر نیست.'
            );
        }

        $detail = $result['TransactionDetail'] ?? [];

        Log::info('SEP VERIFY RESPONSE STRUCTURE', [
            'ref_num_fingerprint' => hash('sha256', $refNum),
            'result_code' => $result['ResultCode'] ?? null,
            'success' => $result['Success'] ?? null,
            'response_keys' => array_keys($result),
            'detail_keys' => is_array($detail)
                ? array_keys($detail)
                : [],
            'original_amount' => is_array($detail)
                ? ($detail['OrginalAmount'] ?? null)
                : null,
            'affective_amount' => is_array($detail)
                ? ($detail['AffectiveAmount'] ?? null)
                : null,
        ]);

        return $result;
    }

}