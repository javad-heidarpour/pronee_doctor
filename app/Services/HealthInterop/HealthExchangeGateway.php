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

        $token = $config['token'] ?? null;
        $request = Http::acceptJson()->asJson()
            ->connectTimeout((int) ($config['connect_timeout'] ?? 5))
            ->timeout((int) ($config['timeout'] ?? 20));

        if ($token) {
            $request = $request->withToken($token);
        }
        if (!empty($config['headers']) && is_array($config['headers'])) {
            $request = $request->withHeaders($config['headers']);
        }

        $response = $request->post($config['url'], [
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
