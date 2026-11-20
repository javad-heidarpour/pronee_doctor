<?php

declare(strict_types=1);

namespace Tests\\Unit\\Portal\\Users;

use App\\Actions\\Portal\\Users\\ListManagedUserPermissionsAction;
use App\\Http\\Middleware\\DoctorAuth;
use ReflectionMethod;
use Tests\\TestCase;

final class ListManagedUserPermissionsActionTest extends TestCase
{
    public function test_every_permission_required_by_supported_secretary_routes_can_be_assigned(): void
    {
        $available = app(ListManagedUserPermissionsAction::class)->handle();
        $resolver = new ReflectionMethod(DoctorAuth::class, 'requiredPermission');
        $resolver->setAccessible(true);
        $middleware = new DoctorAuth();

        $routes = [
            ['portal.growth.index', 'GET'],
            ['portal.clinic-performance.index', 'GET'],
            ['portal.marketing.index', 'GET'],
            ['portal.marketing.store', 'POST'],
            ['portal.marketing.campaigns.update', 'PUT'],
            ['portal.intelligence.health.enroll', 'POST'],
            ['portal.patients.index', 'GET'],
            ['portal.operations.services.store', 'POST'],
        ];

        foreach ($routes as [$routeName, $method]) {
            $permission = $resolver->invoke($middleware, $routeName, $method);

            $this->assertNotNull($permission, "No permission mapping exists for {$routeName}.");
            $this->assertArrayHasKey(
                $permission,
                $available,
                "The secretary assignment screen cannot grant {$permission} required by {$routeName}."
            );
        }
    }

    public function test_marketing_permission_labels_are_persian_and_distinguish_read_from_manage(): void
    {
        $permissions = app(ListManagedUserPermissionsAction::class)->handle();

        $this->assertSame('مشاهده بازاریابی و گزارش رشد کلینیک', $permissions['marketing.view']);
        $this->assertSame('مدیریت کمپین‌ها و ارتباطات بازاریابی', $permissions['marketing.manage']);
    }
}
