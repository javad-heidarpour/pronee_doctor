<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ClinicBrain\WaitlistOfferEligibility;
use Tests\TestCase;

final class WaitlistOfferEligibilityTest extends TestCase
{
    private function eligible(array $overrides = []): bool
    {
        $values = array_merge([
            'status' => 'waiting',
            'consent' => true,
            'preferredClinic' => 12,
            'slotClinic' => 12,
            'from' => '2026-11-22',
            'until' => '2026-11-30',
            'date' => '2026-11-25',
            'period' => 'morning',
            'time' => '10:30:00',
        ], $overrides);

        return app(WaitlistOfferEligibility::class)->matches(
            $values['status'],
            $values['consent'],
            $values['preferredClinic'],
            $values['slotClinic'],
            $values['from'],
            $values['until'],
            $values['date'],
            $values['period'],
            $values['time'],
        );
    }

    public function test_matching_waiting_request_with_contact_consent_is_eligible(): void
    {
        $this->assertTrue($this->eligible());
    }

    public function test_closed_or_already_offered_request_is_not_eligible(): void
    {
        $this->assertFalse($this->eligible(['status' => 'offered']));
        $this->assertFalse($this->eligible(['status' => 'closed']));
    }

    public function test_contact_consent_is_mandatory(): void
    {
        $this->assertFalse($this->eligible(['consent' => false]));
    }

    public function test_clinic_and_date_preferences_are_enforced(): void
    {
        $this->assertFalse($this->eligible(['slotClinic' => 99]));
        $this->assertFalse($this->eligible(['date' => '2026-12-01']));
        $this->assertFalse($this->eligible(['date' => '2026-11-20']));
    }

    public function test_time_period_and_any_period_are_applied(): void
    {
        $this->assertFalse($this->eligible(['time' => '14:00:00']));
        $this->assertTrue($this->eligible(['period' => 'afternoon', 'time' => '14:00:00']));
        $this->assertTrue($this->eligible(['period' => 'any', 'time' => '23:00:00']));
    }

    public function test_any_clinic_is_supported_when_no_clinic_was_selected(): void
    {
        $this->assertTrue($this->eligible(['preferredClinic' => null, 'slotClinic' => 99]));
    }
}
