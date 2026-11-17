<?php

namespace App\Actions\Portal\Shifts;

use App\Actions\Portal\Clinic\EnsureDoctorOwnsActiveClinicAction;
use App\DTOs\Portal\Scheduling\GenerateTimeSlotsData;
use App\Models\Doctor;
use App\Models\DoctorWorkingHour;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateTimeSlotsAction
{
    public function __construct(
        private readonly EnsureDoctorOwnsActiveClinicAction $ensureClinic
    ) {
    }

    /**
     * @return array{generated:int,skipped:int,has_working_hours:bool}
     */
    public function handle(Doctor $doctor, GenerateTimeSlotsData $data): array
    {
        $weeksCount = $data->weeksCount;
        $clinicId = $data->clinicId;
        if ($clinicId !== null) {
            $this->ensureClinic->handle($doctor, $clinicId);
        }

        $query = DoctorWorkingHour::query()
            ->where('doctor_id', $doctor->id)
            ->where('is_active', true)
            ->whereHas('clinic', fn ($clinicQuery) => $clinicQuery
                ->where('doctor_id', $doctor->id)
                ->where('is_active', true));

        if ($clinicId !== null) {
            $query->where('clinic_id', $clinicId);
        }

        $workingHours = $query->get();

        if ($workingHours->isEmpty()) {
            return ['generated' => 0, 'skipped' => 0, 'has_working_hours' => false];
        }

        $generatedCount = 0;
        $skippedCount = 0;
        $baseDate = Carbon::now()->startOfWeek(Carbon::SATURDAY);

        DB::transaction(function () use (
            $workingHours, $weeksCount, $baseDate, $doctor,
            &$generatedCount, &$skippedCount
        ) {
            Doctor::query()->whereKey($doctor->id)->lockForUpdate()->firstOrFail();

            for ($week = 0; $week < $weeksCount; $week++) {
                $currentWeekStart = $baseDate->copy()->addWeeks($week);

                foreach ($workingHours as $workingHour) {
                    $targetDate = $currentWeekStart->copy()->addDays($workingHour->day_of_week);

                    if ($targetDate->isBefore(Carbon::today())) {
                        continue;
                    }

                    $date = $targetDate->format('Y-m-d');
                    $start = Carbon::parse($date . ' ' . $workingHour->start_time);
                    $end = Carbon::parse($date . ' ' . $workingHour->end_time);
                    $duration = (int) $workingHour->duration_minutes;
                    $cursor = $start->copy();

                    while ($cursor->copy()->addMinutes($duration)->lte($end)) {
                        $slotEnd = $cursor->copy()->addMinutes($duration);
                        $startValue = $cursor->format('H:i:s');
                        $endValue = $slotEnd->format('H:i:s');

                        $overlap = TimeSlot::query()
                            ->overlapping((int) $doctor->id, $date, $startValue, $endValue)
                            ->lockForUpdate()
                            ->exists();

                        if ($overlap) {
                            $skippedCount++;
                        } else {
                            TimeSlot::create([
                                'doctor_id' => $doctor->id,
                                'clinic_id' => $workingHour->clinic_id,
                                'date' => $date,
                                'start_time' => $startValue,
                                'end_time' => $endValue,
                                'status' => 'available',
                            ]);
                            $generatedCount++;
                        }

                        $cursor->addMinutes($duration);
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
