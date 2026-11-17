<?php

declare(strict_types=1);

namespace App\Actions\Portal\Shifts;

use App\DTOs\Portal\Scheduling\GenerateTimeSlotsData;
use App\Models\Doctor;
use InvalidArgumentException;

final class GenerateSlotsForAllDoctorsAction
{
    public function __construct(
        private readonly GenerateTimeSlotsAction $generateTimeSlots
    ) {
    }

    /**
     * @return array{doctors:int,generated:int,skipped:int,without_working_hours:int}
     */
    public function handle(int $weeksCount): array
    {
        if ($weeksCount < 1 || $weeksCount > 52) {
            throw new InvalidArgumentException('Weeks count must be between 1 and 52.');
        }

        $summary = [
            'doctors' => 0,
            'generated' => 0,
            'skipped' => 0,
            'without_working_hours' => 0,
        ];

        Doctor::query()
            ->where('is_active', true)
            ->chunkById(100, function ($doctors) use ($weeksCount, &$summary): void {
                foreach ($doctors as $doctor) {
                    $summary['doctors']++;

                    $result = $this->generateTimeSlots->handle(
                        $doctor,
                        new GenerateTimeSlotsData($weeksCount, null)
                    );

                    $summary['generated'] += $result['generated'];
                    $summary['skipped'] += $result['skipped'];

                    if (!$result['has_working_hours']) {
                        $summary['without_working_hours']++;
                    }
                }
            });

        return $summary;
    }
}
