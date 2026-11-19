<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MedicalNetworkProvider;
use App\Models\MedicalReferral;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class ReferralCollaborationOpportunityFinder
{
    public const WINDOW_DAYS = 180;
    public const MAX_OPPORTUNITIES_PER_SERVICE = 4;

    public function providerTypeForService(string $serviceType): ?string
    {
        return match ($serviceType) {
            'laboratory', 'imaging', 'rehabilitation' => $serviceType,
            'consultation' => 'doctor',
            default => null,
        };
    }

    /**
     * Find potential partners from this doctor's actual referral demand.
     * The score is a transparent business-fit heuristic, not a clinical rating.
     */
    public function find(int $doctorId, int $userId, ?string $cityName = null, ?CarbonInterface $now = null, ?array $clinicIds = null): array
    {
        $now ??= now();
        $from = $now->copy()->subDays(self::WINDOW_DAYS);

        $demand = MedicalReferral::query()
            ->where('referring_doctor_id', $doctorId)
            ->when($clinicIds !== null, fn ($query) => $query->whereIn('source_clinic_id', $clinicIds))
            ->where('created_at', '>=', $from)
            ->selectRaw('service_type, COUNT(*) as demand_count')
            ->groupBy('service_type')
            ->orderByDesc('demand_count')
            ->get();

        if ($demand->isEmpty()) {
            return ['window_days' => self::WINDOW_DAYS, 'items' => []];
        }

        $services = $demand->map(fn ($row) => $row->service_type)->unique()->values();
        $providerTypes = $services->map(fn ($service) => $this->providerTypeForService($service))->filter()->unique()->values();

        if ($providerTypes->isEmpty()) {
            return ['window_days' => self::WINDOW_DAYS, 'items' => []];
        }

        $providers = MedicalNetworkProvider::query()
            ->where('status', 'active')
            ->where('is_verified', true)
            ->whereIn('provider_type', $providerTypes)
            ->where(function ($query) use ($doctorId, $userId): void {
                $query->whereNull('doctor_id')->orWhere('doctor_id', '!=', $doctorId);
            })
            ->where('created_by_user_id', '!=', $userId)
            ->get(['id', 'name', 'provider_type', 'city', 'address', 'phone', 'specialties', 'services']);

        if ($providers->isEmpty()) {
            return ['window_days' => self::WINDOW_DAYS, 'items' => []];
        }

        $providerIds = $providers->pluck('id')->all();
        $history = MedicalReferral::query()
            ->whereIn('target_provider_id', $providerIds)
            ->where('created_at', '>=', $from)
            ->get(['target_provider_id', 'status', 'created_at', 'accepted_at']);

        $historyByProvider = $history->groupBy(fn ($row) => (int) $row->target_provider_id);
        $city = $this->normalize($cityName);
        $items = [];

        foreach ($demand as $need) {
            $providerType = $this->providerTypeForService($need->service_type);
            if (!$providerType) {
                continue;
            }

            $candidates = $providers->where('provider_type', $providerType)->map(function ($provider) use ($need, $historyByProvider, $city): array {
                $records = $historyByProvider->get((int) $provider->id, collect());
                $sample = $records->count();
                $accepted = $records->filter(fn ($row) => in_array($row->status, ['accepted', 'scheduled', 'in_progress', 'completed'], true));
                $completed = $records->where('status', 'completed');
                $responseHours = $records->filter(fn ($row) => $row->created_at && $row->accepted_at)
                    ->map(fn ($row) => max(0, $row->created_at->diffInMinutes($row->accepted_at, false) / 60));
                $sameCity = $city !== '' && $this->normalize($provider->city) === $city;
                $demandPoints = min((int) $need->demand_count, 10) * 4;
                $localityPoints = $sameCity ? 30 : 0;
                $experiencePoints = min($sample, 5) * 3;
                $completionRate = $sample >= 5 && $accepted->isNotEmpty()
                    ? round($completed->count() / $accepted->count() * 100, 1)
                    : null;
                $completionPoints = $completionRate === null ? 0 : (int) round($completionRate * 0.15);
                $score = min(100, $demandPoints + $localityPoints + $experiencePoints + $completionPoints);

                $reasons = ['تقاضای ثبت‌شده برای این خدمت: ' . (int) $need->demand_count . ' ارجاع در ۱۸۰ روز'];
                if ($sameCity) {
                    $reasons[] = 'در همان شهر مطب شما';
                }
                if ($sample > 0) {
                    $reasons[] = 'دارای سابقه ' . $sample . ' ارجاع در شبکه';
                }
                if ($completionRate !== null) {
                    $reasons[] = 'نرخ تکمیل گردش ارجاع: ' . $completionRate . '٪ (حداقل ۵ نمونه)';
                }

                return [
                    'provider_id' => (int) $provider->id,
                    'name' => $provider->name,
                    'provider_type' => $provider->provider_type,
                    'city' => $provider->city,
                    'address' => $provider->address,
                    'phone' => $provider->phone,
                    'specialties' => $provider->specialties ?? [],
                    'services' => $provider->services ?? [],
                    'demand_count' => (int) $need->demand_count,
                    'history_sample' => $sample,
                    'completion_rate' => $completionRate,
                    'average_response_hours' => $responseHours->isNotEmpty() ? round($responseHours->avg(), 1) : null,
                    'same_city' => $sameCity,
                    'fit_score' => $score,
                    'reasons' => $reasons,
                ];
            })->sortByDesc('fit_score')->take(self::MAX_OPPORTUNITIES_PER_SERVICE)->values();

            if ($candidates->isNotEmpty()) {
                $items[] = [
                    'service_type' => $need->service_type,
                    'demand_count' => (int) $need->demand_count,
                    'opportunities' => $candidates->all(),
                ];
            }
        }

        return ['window_days' => self::WINDOW_DAYS, 'items' => $items];
    }

    private function normalize(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }
}
