<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ReferralNetworkIntelligence;
use Tests\TestCase;

final class ReferralNetworkIntelligenceTest extends TestCase
{
    public function test_it_declares_a_bounded_reporting_window_and_minimum_sample(): void
    {
        $this->assertSame(180, ReferralNetworkIntelligence::WINDOW_DAYS);
        $this->assertSame(5, ReferralNetworkIntelligence::MINIMUM_SAMPLE);
    }

    public function test_empty_provider_set_returns_no_patient_level_data(): void
    {
        $result = (new ReferralNetworkIntelligence())->forProviders(collect());

        $this->assertSame(180, $result['window_days']);
        $this->assertSame(5, $result['minimum_sample']);
        $this->assertSame([], $result['providers']);
    }
}
