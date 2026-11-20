<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\PatientAccountAccess;
use Tests\TestCase;

final class PatientAccountAccessTest extends TestCase
{
    public function test_owner_access_is_not_limited_by_the_stored_permission_list(): void
    {
        $access = new PatientAccountAccess([
            'access_type' => 'owner',
            'permissions' => [],
        ]);

        $this->assertTrue($access->allows('view_records'));
        $this->assertTrue($access->allows('manage_caregivers'));
    }

    public function test_caregiver_access_requires_an_explicit_permission(): void
    {
        $access = new PatientAccountAccess([
            'access_type' => 'caregiver',
            'permissions' => ['view_summary', 'view_followups'],
        ]);

        $this->assertTrue($access->allows('view_summary'));
        $this->assertTrue($access->allows('view_followups'));
        $this->assertFalse($access->allows('view_appointments'));
        $this->assertFalse($access->allows('view_records'));
    }

    public function test_unknown_access_types_are_denied(): void
    {
        $access = new PatientAccountAccess([
            'access_type' => 'unknown',
            'permissions' => ['view_summary', 'view_records'],
        ]);

        $this->assertFalse($access->allows('view_summary'));
    }
}
