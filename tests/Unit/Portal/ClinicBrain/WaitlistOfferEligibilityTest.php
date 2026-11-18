<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\ClinicBrain;

use App\Services\ClinicBrain\WaitlistOfferEligibility;
use Tests\TestCase;

final class WaitlistOfferEligibilityTest extends TestCase
{
    public function test_it_matches_only_consenting_waiting_patients_within_date_clinic_and_period_preferences(): void
    {
        $matcher = app(WaitlistOfferEligibility::class);

        $this->assertTrue($matcher->matches('waiting', true, 4, 4, '2026-11-20', '2026-11-30', '2026-11-25', 'morning', '10:30:00'));
        $this->assertFalse($matcher->matches('waiting', false, 4, 4, '2026-11-20', '2026-11-30', '2026-11-25', 'morning', '10:30:00'));
        $this->assertFalse($matcher->matches('offered', true, 4, 4, '2026-11-20', '2026-11-30', '2026-11-25', 'morning', '10:30:00'));
        $this->assertFalse($matcher->matches('waiting', true, 5, 4, '2026-11-20', '2026-11-30', '2026-11-25', 'morning', '10:30:00'));
        $this->assertFalse($matcher->matches('waiting', true, 4, 4, '2026-11-20', '2026-11-30', '2026-12-01', 'morning', '10:30:00'));
        $this->assertFalse($matcher->matches('waiting', true, 4, 4, '2026-11-20', '2026-11-30', '2026-11-25', 'morning', '13:00:00'));
        $this->assertTrue($matcher->matches('waiting', true, null, 9, '2026-11-20', '2026-11-30', '2026-11-25', 'afternoon', '13:00:00'));
    }

    public function test_unknown_period_is_never_eligible(): void
    {
        $this->assertFalse(app(WaitlistOfferEligibility::class)->matches(
            'waiting', true, null, 1, '2026-11-20', '2026-11-30', '2026-11-25', 'overnight', '23:00:00'
        ));
    }
}
