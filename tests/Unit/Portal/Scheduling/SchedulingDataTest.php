<?php

namespace Tests\Unit\Portal\Scheduling;

use App\DTOs\Portal\Scheduling\TimeSlotData;
use App\DTOs\Portal\Scheduling\WorkingHourData;
use PHPUnit\Framework\TestCase;

class SchedulingDataTest extends TestCase
{
    public function test_time_slot_data_maps_validated_fields_to_typed_properties(): void
    {
        $data = TimeSlotData::fromValidated([
            'clinic_id' => '12',
            'date' => '2026-11-18',
            'start_time' => '09:00',
            'end_time' => '09:30',
        ]);

        $this->assertSame(12, $data->clinicId);
        $this->assertSame('2026-11-18', $data->date);
        $this->assertSame('09:00', $data->startTime);
        $this->assertSame('09:30', $data->endTime);
    }

    public function test_working_hour_data_maps_and_serializes_validated_fields(): void
    {
        $data = WorkingHourData::fromValidated([
            'clinic_id' => '7',
            'day_of_week' => '2',
            'duration_minutes' => '20',
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);

        $this->assertSame(7, $data->clinicId);
        $this->assertSame(2, $data->dayOfWeek);
        $this->assertSame(20, $data->durationMinutes);
        $this->assertSame([
            'clinic_id' => 7,
            'day_of_week' => 2,
            'duration_minutes' => 20,
            'start_time' => '08:00',
            'end_time' => '12:00',
        ], $data->toArray());
    }
}
