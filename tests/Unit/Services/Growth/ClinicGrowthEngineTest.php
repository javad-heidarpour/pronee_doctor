<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Growth;

use PHPUnit\Framework\TestCase;

final class ClinicGrowthEngineTest extends TestCase
{
    public function test_growth_dashboard_contract_documents_aggregate_only_patient_data(): void
    {
        $source = file_get_contents(base_path('app/Services/Growth/ClinicGrowthEngine.php'));

        $this->assertStringContainsString('inactive_over_90_days', $source);
        $this->assertStringContainsString('successful_payments_by_paid_at', $source);
        $this->assertStringContainsString('clinic_marketing_events', $source);
        $this->assertStringContainsString('No patient names, phones or clinical details', $source);
    }
}
