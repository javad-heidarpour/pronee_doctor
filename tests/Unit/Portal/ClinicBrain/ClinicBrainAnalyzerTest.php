<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\ClinicBrain;

use App\Services\ClinicBrain\ClinicBrainAnalyzer;
use Tests\TestCase;

final class ClinicBrainAnalyzerTest extends TestCase
{
    public function test_empty_clinic_scope_returns_safe_empty_analysis_without_database_queries(): void
    {
        $result = app(ClinicBrainAnalyzer::class)->analyze(10, []);

        $this->assertSame(0, $result['metrics']['historical_appointments']);
        $this->assertSame(0, $result['metrics']['upcoming_capacity']);
        $this->assertSame([], $result['days']);
        $this->assertNotEmpty($result['insights']);
    }
}
