<?php

namespace Tests\Unit\Portal\Scheduling;

use App\Http\Requests\Portal\ListTimeSlotsRequest;
use App\Http\Requests\Portal\ListWorkingHoursRequest;
use App\Http\Requests\Portal\GenerateTimeSlotsRequest;
use App\Http\Requests\Portal\SaveWorkingHourRequest;
use App\Http\Requests\Portal\StoreTimeSlotRequest;
use App\Http\Requests\Portal\UpdateTimeSlotRequest;
use App\Http\Requests\Portal\UpdateSecretaryAssignmentsRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
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

    public function test_update_time_slot_request_rejects_past_dates(): void
    {
        Carbon::setTestNow('2026-11-17 12:00:00');

        try {
            $request = new UpdateTimeSlotRequest();
            $dateRules = ['date' => $request->rules()['date']];

            $pastDate = Validator::make([
                'date' => '2026-11-16',
            ], $dateRules);

            $today = Validator::make([
                'date' => '2026-11-17',
            ], $dateRules);

            $this->assertTrue($pastDate->fails());
            $this->assertFalse($today->fails());
        } finally {
            Carbon::setTestNow();
        }
    }

    public static function listRequestClasses(): array
    {
        return [
            'time slots' => [ListTimeSlotsRequest::class],
            'working hours list' => [ListWorkingHoursRequest::class],
            'generate time slots' => [GenerateTimeSlotsRequest::class],
            'save working hours' => [SaveWorkingHourRequest::class],
            'store time slot' => [StoreTimeSlotRequest::class],
            'update time slot' => [UpdateTimeSlotRequest::class],
            'secretary assignments' => [UpdateSecretaryAssignmentsRequest::class],
        ];
    }
}
