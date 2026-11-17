<?php

namespace Tests\Unit\Portal\Scheduling;

use App\Http\Requests\Portal\ListTimeSlotsRequest;
use App\Http\Requests\Portal\ListWorkingHoursRequest;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SchedulingRequestAuthorizationTest extends TestCase
{
    #[DataProvider('listRequestClasses')]
    public function test_list_requests_reject_guests(string $requestClass): void
    {
        $request = new $requestClass();
        $request->setUserResolver(fn ($guard = null) => null);

        $this->assertFalse($request->authorize());
    }

    #[DataProvider('listRequestClasses')]
    public function test_list_requests_reject_inactive_users(string $requestClass): void
    {
        $user = new User();
        $user->setAttribute('is_active', false);

        $request = new $requestClass();
        $request->setUserResolver(fn ($guard = null) => $user);

        $this->assertFalse($request->authorize());
    }

    #[DataProvider('listRequestClasses')]
    public function test_list_requests_allow_active_users(string $requestClass): void
    {
        $user = new User();
        $user->setAttribute('is_active', true);

        $request = new $requestClass();
        $request->setUserResolver(fn ($guard = null) => $user);

        $this->assertTrue($request->authorize());
    }

    public static function listRequestClasses(): array
    {
        return [
            'time slots' => [ListTimeSlotsRequest::class],
            'working hours' => [ListWorkingHoursRequest::class],
        ];
    }
}
