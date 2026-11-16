<?php

namespace App\Actions\Portal\Shifts;

use App\Models\Doctor;
use App\Models\DoctorWorkingHour;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateTimeSlotsAction
{
    /**
     * @return array{generated:int,skipped:int,has_working_hours:bool}
     */
    public function handle(Doctor $doctor, int $weeksCount, ?int $clinicId = null): array
    {
        $query = DoctorWorkingHour::query()
            ->where('doctor_id', $doctor->id)
            ->where('is_active', true);

        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }

        $workingHours = $query->get();

        if ($workingHours->isEmpty()) {
            return [
                'generated' => 0,
                'skipped' => 0,
                'has_working_hours' => false,
            ];
        }

        $generatedCount = 0;
        $skippedCount = 0;
        $baseDate = Carbon::now()->startOfWeek(Carbon::SATURDAY);

        DB::transaction(function () use (
            $workingHours,
            $weeksCount,
            $baseDate,
            $doctor,
            &$generatedCount,
            &$skippedCount
        ) {
            for ($week = 0; $week < $weeksCount; $week++) {
                $currentWeekStart = $baseDate->copy()->addWeeks($week);

                foreach ($workingHours as $workingHour) {
                    $targetDate = $currentWeekStart
                        ->copy()
                        ->addDays($workingHour->day_of_week);

                    if ($targetDate->isBefore(Carbon::today())) {
                        continue;
                    }

                    $startTime = Carbon::parse(
                        $targetDate->format('Y-m-d') . ' ' . $workingHour->start_time
                    );
                    $endTime = Carbon::parse(
                        $targetDate->format('Y-m-d') . ' ' . $workingHour->end_time
                    );
                    $duration = (int) $workingHour->duration_minutes;
                    $currentSlotTime = $startTime->copy();

                    while (
                        $currentSlotTime->copy()
                            ->addMinutes($duration)
                            ->lte($endTime)
                    ) {
                        $slotEndTime = $currentSlotTime->copy()->addMinutes($duration);

                        $exists = TimeSlot::query()
                            ->where('doctor_id', $doctor->id)
                            ->where('clinic_id', $workingHour->clinic_id)
                            ->where('date', $targetDate->format('Y-m-d'))
                            ->where('start_time', $currentSlotTime->format('H:i:s'))
                            ->exists();

                        if ($exists) {
                            $skippedCount++;
                        } else {
                            TimeSlot::create([
                                'doctor_id' => $doctor->id,
                                'clinic_id' => $workingHour->clinic_id,
                                'date' => $targetDate->format('Y-m-d'),
                                'start_time' => $currentSlotTime->format('H:i:s'),
                                'end_time' => $slotEndTime->format('H:i:s'),
                                'status' => 'available',
                            ]);

                            $generatedCount++;
                        }

                        $currentSlotTime->addMinutes($duration);
                    }
                }
            }
        });

        return [
            'generated' => $generatedCount,
            'skipped' => $skippedCount,
            'has_working_hours' => true,
        ];
    }
}