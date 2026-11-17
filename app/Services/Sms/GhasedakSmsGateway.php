<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Contracts\Sms\SmsGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class GhasedakSmsGateway implements SmsGateway
{
    private const ENDPOINT = 'https://gateway.ghasedak.me/rest/api/v1/WebService/SendOtpSMS';

    public function sendTemplate(
        string $phone,
        string $templateName,
        array $inputs,
        string $referenceId
    ): array {
        $apiKey = config('services.ghasedak.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('کلید API قاصدک تنظیم نشده است.');
        }

        $response = Http::timeout(20)
            ->acceptJson()
            ->withHeaders([
                'ApiKey' => $apiKey,
            ])
            ->post(self::ENDPOINT, [
                'sendDate' => null,
                'receptors' => [[
                    'mobile' => $phone,
                    'clientReferenceId' => $referenceId,
                ]],
                'templateName' => $templateName,
                'inputs' => $inputs,
                'isVoice' => false,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'ارسال پیامک ناموفق بود: ' . $response->status()
            );
        }

        $payload = $response->json() ?? [];

        if (
            is_array($payload)
            && array_key_exists('IsSuccess', $payload)
            && $payload['IsSuccess'] === false
        ) {
            throw new RuntimeException(
                'ارسال پیامک توسط سرویس‌دهنده تأیید نشد.'
            );
        }

        return is_array($payload) ? $payload : [];
    }
}
