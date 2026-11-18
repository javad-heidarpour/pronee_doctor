<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\ClinicIntelligence;

use App\Services\ClinicIntelligence\InventoryDemandForecaster;
use Tests\TestCase;

final class InventoryDemandForecasterTest extends TestCase
{
    public function test_empty_clinic_scope_returns_safe_empty_forecast_without_database_queries(): void
    {
        $result = app(InventoryDemandForecaster::class)->forecast(10, [], 30);

        $this->assertSame(30, $result['horizon_days']);
        $this->assertSame([], $result['items']);
        $this->assertSame([
            'total' => 0,
            'critical' => 0,
            'warning' => 0,
            'insufficient_data' => 0,
        ], $result['summary']);
    }

    public function test_forecast_horizon_is_bounded_to_supported_range(): void
    {
        $result = app(InventoryDemandForecaster::class)->forecast(10, [], 365);

        $this->assertSame(90, $result['horizon_days']);
    }
}
