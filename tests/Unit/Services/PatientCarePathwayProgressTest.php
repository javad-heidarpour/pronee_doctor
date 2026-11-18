<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\PatientCarePathwayProgress;
use PHPUnit\Framework\TestCase;

final class PatientCarePathwayProgressTest extends TestCase
{
    public function test_only_non_empty_drafts_can_be_activated(): void
    {
        $progress = new PatientCarePathwayProgress();
        $this->assertTrue($progress->canActivate('draft', 2));
        $this->assertFalse($progress->canActivate('active', 2));
        $this->assertFalse($progress->canActivate('draft', 0));
    }

    public function test_pathway_completes_only_when_every_step_is_verified(): void
    {
        $progress = new PatientCarePathwayProgress();
        $this->assertFalse($progress->isComplete(collect()));
        $this->assertFalse($progress->isComplete(collect([(object)['status'=>'verified'], (object)['status'=>'patient_reported']])));
        $this->assertTrue($progress->isComplete(collect([(object)['status'=>'verified'], (object)['status'=>'verified']])));
    }
}
