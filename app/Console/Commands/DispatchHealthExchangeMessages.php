<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\HealthExchangeMessage;
use App\Services\HealthInterop\HealthExchangeGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DispatchHealthExchangeMessages extends Command
{
    protected $signature = 'health:dispatch-exchange {--limit=50}';
    protected $description = 'Dispatch pending health exchange messages to configured official gateways';

    public function handle(HealthExchangeGateway $gateway): int
    {
        $limit = max(1, min(200, (int) $this->option('limit')));
        $ids = HealthExchangeMessage::query()
            ->whereIn('status', [
                HealthExchangeMessage::PENDING,
                HealthExchangeMessage::FAILED,
                HealthExchangeMessage::BLOCKED,
            ])
            ->where(fn ($query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
            ->orderBy('id')->limit($limit)->pluck('id');

        foreach ($ids as $id) {
            $message = DB::transaction(function () use ($id) {
                $row = HealthExchangeMessage::query()->whereKey($id)->lockForUpdate()->first();
                if (!$row || !in_array($row->status, [
                    HealthExchangeMessage::PENDING,
                    HealthExchangeMessage::FAILED,
                    HealthExchangeMessage::BLOCKED,
                ], true)) {
                    return null;
                }
                $row->update(['status' => HealthExchangeMessage::PROCESSING, 'attempts' => $row->attempts + 1]);
                return $row->fresh();
            });
            if (!$message) {
                continue;
            }

            try {
                $result = $gateway->dispatch($message);
                $message->update([
                    'status' => HealthExchangeMessage::SENT,
                    'remote_tracking_id' => $result['tracking_id'],
                    'response_payload' => $result['response'],
                    'sent_at' => now(),
                    'last_error' => null,
                ]);
                $this->info("Message {$message->id} accepted.");
            } catch (Throwable $exception) {
                $disabled = str_contains($exception->getMessage(), 'پیکربندی یا فعال نشده');
                $message->update([
                    'status' => $disabled ? HealthExchangeMessage::BLOCKED : HealthExchangeMessage::FAILED,
                    'last_error' => mb_substr($exception->getMessage(), 0, 2000),
                    'available_at' => $disabled ? null : now()->addMinutes(min(360, 2 ** min(8, (int) $message->attempts))),
                ]);
                $this->warn("Message {$message->id}: ".$exception->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
