<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ReferralCollaborationOpportunityFinder;
use Tests\TestCase;

final class ReferralCollaborationOpportunityFinderTest extends TestCase
{
    public function test_service_types_map_to_the_matching_network_provider_type(): void
    {
        $finder = new ReferralCollaborationOpportunityFinder();

        $this->assertSame('laboratory', $finder->providerTypeForService('laboratory'));
        $this->assertSame('imaging', $finder->providerTypeForService('imaging'));
        $this->assertSame('rehabilitation', $finder->providerTypeForService('rehabilitation'));
        $this->assertSame('doctor', $finder->providerTypeForService('consultation'));
    }

    public function test_unknown_service_type_is_not_matched_to_a_provider(): void
    {
        $this->assertNull((new ReferralCollaborationOpportunityFinder())->providerTypeForService('unknown'));
    }
}
