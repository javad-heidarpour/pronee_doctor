<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MedicalReferral;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class ReferralNetworkIntelligence
{
    public const WINDOW_DAYS = 180;
    public const MINIMUM_SAMPLE = 5;

    /** Aggregate operational indicators only; never a measure of clinical quality. */
    public function forProviders(Collection $providers, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $ids = $providers->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($ids === []) {
            return ['window_days' => self::WINDOW_DAYS, 'minimum_sample' => self::MINIMUM_SAMPLE, 'providers' => []];
        }

        $rows = MedicalReferral::query()
            ->whereIn('target_provider_id', $ids)
            ->where('created_at', '>=', $now->copy()->subDays(self::WINDOW_DAYS))
            ->get(['target_provider_id', 'status', 'created_at', 'accepted_at', 'scheduled_at']);

        $grouped = $rows->groupBy(fn ($row) => (int) $row->target_provider_id);
        $metrics = [];

        foreach ($providers as $provider) {
            $items = $grouped->get((int) $provider->id, collect());
            $sample = $items->count();
            $eligible = $items->reject(fn ($item) => $item->status === 'cancelled');
            $accepted = $items->filter(fn ($item) => in_array($item->status, ['accepted', 'scheduled', 'in_progress', 'completed'], true));
            $completed = $items->where('status', 'completed');
            $responseMinutes = $items->filter(fn ($item) => $item->accepted_at && $item->created_at)
                ->map(fn ($item) => max(0, $item->created_at->diffInMinutes($item->accepted_at, false)));

            $overdue = $items->filter(function ($item) use ($now): bool {
                if ($item->status === 'sent') {
                    return $item->created_at && $item->created_at->lte($now->copy()->subHours(48));
                }

                return in_array($item->status, ['accepted', 'scheduled'], true)
                    && $item->scheduled_at && $item->scheduled_at->lt($now);
            })->count();

            $enough = $sample >= self::MINIMUM_SAMPLE;
            $metrics[] = [
                'provider_id' => (int) $provider->id,
                'provider_name' => $provider->name,
                'provider_type' => $provider->provider_type,
                'city' => $provider->city,
                'sample_size' => $sample,
                'accepted_count' => $accepted->count(),
                'completed_count' => $completed->count(),
                'overdue_count' => $overdue,
                'acceptance_rate' => $enough && $eligible->isNotEmpty() ? round($accepted->count() / $eligible->count() * 100, 1) : null,
                'completion_rate' => $enough && $accepted->isNotEmpty() ? round($completed->count() / $accepted->count() * 100, 1) : null,
                'average_response_hours' => $responseMinutes->isNotEmpty() ? round($responseMinutes->avg() / 60, 1) : null,
                'data_sufficient' => $enough,
            ];
        }

        usort($metrics, fn (array $a, array $b): int => [$b['data_sufficient'], $b['completed_count'], $a['overdue_count']] <=> [$a['data_sufficient'], $a['completed_count'], $b['overdue_count']]);

        return ['window_days' => self::WINDOW_DAYS, 'minimum_sample' => self::MINIMUM_SAMPLE, 'providers' => $metrics];
    }
}
