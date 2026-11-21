<?php

declare(strict_types=1);

namespace Tests\\Unit\\Portal\\Dashboard;

use App\\Actions\\Portal\\Dashboard\\BuildDashboardAction;
use PHPUnit\\Framework\\TestCase;
use ReflectionMethod;

final class BuildDashboardFinancePermissionTest extends TestCase
{
    private function allows(mixed $permissions): bool
    {
        $method = new ReflectionMethod(BuildDashboardAction::class, 'hasFinancePermission');
        $method->setAccessible(true);

        return $method->invoke(new BuildDashboardAction(), $permissions);
    }

    public function test_secretary_without_finance_permission_cannot_view_financial_metrics(): void
    {
        $this->assertFalse($this->allows(null));
        $this->assertFalse($this->allows([]));
        $this->assertFalse($this->allows(['appointments.view']));
        $this->assertFalse($this->allows('["appointments.view"]'));
    }

    public function test_finance_view_and_manage_permissions_allow_financial_metrics(): void
    {
        $this->assertTrue($this->allows(['finance.view']));
        $this->assertTrue($this->allows(['finance.manage']));
        $this->assertTrue($this->allows('["finance.view"]'));
        $this->assertTrue($this->allows('["finance.manage"]'));
    }

    public function test_malformed_permission_payload_is_denied_by_default(): void
    {
        $this->assertFalse($this->allows('{invalid-json'));
        $this->assertFalse($this->allows('finance.view'));
    }
}
