<?php

declare(strict_types=1);

namespace Tests\\Unit\\Services\\PopulationHealth;

use App\\Services\\PopulationHealth\\PopulationHealthDisclosure;
use PHPUnit\\Framework\\TestCase;

final class PopulationHealthDisclosureTest extends TestCase
{
    public function test_cohorts_with_fewer_than_five_distinct_patients_are_suppressed(): void
    {
        $policy = new PopulationHealthDisclosure();

        foreach ([0, 1, 2, 3, 4] as $count) {
            self::assertFalse($policy->canDisclose($count));
        }
    }

    public function test_cohort_with_exactly_five_distinct_patients_can_be_disclosed(): void
    {
        self::assertTrue((new PopulationHealthDisclosure())->canDisclose(5));
    }

    public function test_larger_cohorts_can_be_disclosed(): void
    {
        self::assertTrue((new PopulationHealthDisclosure())->canDisclose(100));
    }
}
