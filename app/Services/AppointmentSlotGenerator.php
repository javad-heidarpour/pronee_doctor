<?php

namespace App\Services;

use App\Actions\Portal\Shifts\GenerateSlotsForAllDoctorsAction;
use App\Actions\Portal\Shifts\GenerateTimeSlotsAction;
use App\DTOs\Portal\Scheduling\GenerateTimeSlotsData;
use App\Models\Doctor;

/**
 * @deprecated Use GenerateTimeSlotsAction or GenerateSlotsForAllDoctorsAction.
 * This adapter remains for compatibility with older callers.
 */
class AppointmentSlotGenerator
{
    public function __construct(
        private readonly GenerateTimeSlotsAction $generateAction,
        private readonly GenerateSlotsForAllDoctorsAction $generateAllAction
    ) {
    }

    public function generateForDoctor(Doctor $doctor, int $days = 7): int
    {
        $weeks = $this->weeksFromDays($days);

        return $this->generateAction->handle(
            $doctor,
            new GenerateTimeSlotsData($weeks, null)
        )['generated'];
    }

    public function generateForAllDoctors(int $days = 7): void
    {
        $this->generateAllAction->handle($this->weeksFromDays($days));
    }

    private function weeksFromDays(int $days): int
    {
        return max(1, (int) ceil(max(1, $days) / 7));
    }
}
