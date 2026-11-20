<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Http\Middleware\DoctorAuth;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

final class DoctorAuthPermissionMapTest extends TestCase
{
    #[DataProvider('secretaryRoutePermissions')]
    public function test_secretary_routes_match_the_permissions_enforced_by_their_controllers(
        string $routeName,
        string $httpMethod,
        string $expectedPermission
    ): void {
        $this->assertSame(
            $expectedPermission,
            $this->resolvePermission($routeName, $httpMethod)
        );
    }

    public function test_sensitive_routes_without_a_secretary_permission_remain_unmapped(): void
    {
        $this->assertNull($this->resolvePermission('portal.intelligence.index', 'GET'));
        $this->assertNull($this->resolvePermission('portal.intelligence.simulate', 'POST'));
        $this->assertNull($this->resolvePermission('portal.medical-network.review', 'GET'));
        $this->assertNull($this->resolvePermission('portal.medical-network.contracts.index', 'GET'));
    }

    public static function secretaryRoutePermissions(): array
    {
        return [
            'patient access requests list' => ['portal.patient-access-requests.index', 'GET', 'patients.clinical.manage'],
            'patient access request approval' => ['portal.patient-access-requests.approve', 'PATCH', 'patients.clinical.manage'],
            'patient access request rejection' => ['portal.patient-access-requests.reject', 'PATCH', 'patients.clinical.manage'],
            'patient retention' => ['portal.patient-retention.index', 'GET', 'patients.view'],
            'clinic growth' => ['portal.growth.index', 'GET', 'marketing.view'],
            'clinic performance' => ['portal.clinic-performance.index', 'GET', 'marketing.view'],
            'revenue optimizer' => ['portal.revenue-optimizer.index', 'GET', 'finance.view'],
            'medical network overview' => ['portal.medical-network.index', 'GET', 'appointments.view'],
            'medical referral create' => ['portal.medical-network.referrals.store', 'POST', 'appointments.manage'],
            'medical referral update' => ['portal.medical-network.referrals.update', 'PATCH', 'appointments.manage'],
            'inventory settings' => ['portal.intelligence.inventory.settings', 'POST', 'inventory.manage'],
            'preventive program create' => ['portal.intelligence.health.programs.store', 'POST', 'patients.manage'],
            'preventive program enrollment' => ['portal.intelligence.health.enroll', 'POST', 'patients.manage'],
            'preventive checkup' => ['portal.intelligence.health.checkup', 'POST', 'patients.manage'],
            'preventive membership withdrawal' => ['portal.intelligence.health.withdraw', 'POST', 'patients.manage'],
            'wellness points' => ['portal.intelligence.health.points', 'POST', 'patients.manage'],
            'clinic brain overview' => ['portal.clinic-brain.index', 'GET', 'appointments.view'],
            'clinic brain waitlist mutation' => ['portal.clinic-brain.waitlist.store', 'POST', 'appointments.manage'],
            'marketing overview' => ['portal.marketing.index', 'GET', 'marketing.view'],
            'marketing campaign mutation' => ['portal.marketing.store', 'POST', 'marketing.manage'],
            'procurement overview' => ['portal.procurement.index', 'GET', 'inventory.view'],
            'procurement supplier mutation' => ['portal.procurement.suppliers.store', 'POST', 'inventory.manage'],
        ];
    }

    private function resolvePermission(string $routeName, string $httpMethod): ?string
    {
        $resolver = new ReflectionMethod(DoctorAuth::class, 'requiredPermission');
        $resolver->setAccessible(true);

        return $resolver->invoke(new DoctorAuth(), $routeName, $httpMethod);
    }
}
