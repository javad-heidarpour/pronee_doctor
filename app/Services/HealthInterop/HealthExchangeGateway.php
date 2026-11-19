<?php

declare(strict_types=1);

namespace App\Services\HealthInterop;

use App\Models\HealthExchangeMessage;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class HealthExchangeGateway
{
    public function dispatch(HealthExchangeMessage $message): array
    {
        $destination = $message->destination;
        $config = config("health_interop.destinations.{$destination}");

        if (!is_array($config) || empty($config['enabled']) || empty($config['contract_approved']) || empty($config['url'])) {
            throw new RuntimeException("اتصال مقصد {$destination} پیکربندی یا فعال نشده است.");
        }

        // نشانی مقصد از تنظیمات سرور می‌آید، اما همچنان باید در فهرست میزبان‌های
        // مورد تأیید باشد؛ این کنترل از ارسال داده سلامت به مقصد اشتباه یا داخلی جلوگیری می‌کند.
        $url = (string) $config['url'];
        $parsedUrl = parse_url($url);
        $host = strtolower(rtrim((string) ($parsedUrl['host'] ?? ''), '.'));
        $allowedHosts = array_map(
            static fn ($value) => strtolower(rtrim(trim((string) $value), '.')),
            (array) ($config['allowed_hosts'] ?? [])
        );

        if (
            ($parsedUrl['scheme'] ?? null) !== 'https' ||
            $host === '' ||
            isset($parsedUrl['user']) ||
            isset($parsedUrl['pass']) ||
            ! in_array($host, $allowedHosts, true)
        ) {
            throw new RuntimeException('نشانی مقصد تبادل سلامت امن یا در فهرست میزبان‌های مجاز نیست.');
        }

        // تغییر مسیر HTTP را می‌بندیم تا مقصد مجاز نتواند درخواست را به میزبان دیگری هدایت کند.
        $token = $config['token'] ?? null;
        $request = Http::acceptJson()->asJson()
            ->withOptions(['allow_redirects' => false, 'verify' => true])
            ->connectTimeout((int) ($config['connect_timeout'] ?? 5))
            ->timeout((int) ($config['timeout'] ?? 20));

        if ($token) {
            $request = $request->withToken($token);
        }
        if (!empty($config['headers']) && is_array($config['headers'])) {
            $request = $request->withHeaders($config['headers']);
        }

        $response = $request->post($url, [
            'message_id' => $message->idempotency_key,
            'event_type' => $message->event_type,
            'payload' => $message->payload,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('مقصد تبادل سلامت پاسخ موفق نداد؛ HTTP '.$response->status());
        }

        $body = $response->json();
        if (!is_array($body) || (($body['accepted'] ?? false) !== true)) {
            throw new RuntimeException('پاسخ مقصد فاقد تأیید صریح پذیرش پیام است.');
        }

        return [
            'tracking_id' => isset($body['tracking_id']) ? (string) $body['tracking_id'] : null,
            'response' => $body,
        ];
    }
}
