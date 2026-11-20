<?php

namespace App\Actions\Portal\Shifts;

use App\Actions\Portal\Clinic\EnsureDoctorOwnsActiveClinicAction;
use App\DTOs\Portal\Scheduling\GenerateTimeSlotsData;
use App\Models\Doctor;
use App\Models\DoctorWorkingHour;
use App\Models\DoctorHoliday;
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
        $generatedCount = 0;
        $skippedCount = 0;
        $hasWorkingHours = false;
        $baseDate = Carbon::now()->startOfWeek(Carbon::SATURDAY);

        DB::transaction(function () use (
            $doctor,
            $data,
            $baseDate,
            &$generatedCount,
            &$skippedCount,
            &$hasWorkingHours
        ) {
            // تمام عملیات زمان‌بندی یک پزشک با قفل همان پزشک سریالی می‌شوند.
            Doctor::query()->whereKey($doctor->id)->lockForUpdate()->firstOrFail();

            // مطب‌های فعال در همین تراکنش قفل می‌شوند تا حین تولید غیرفعال نشوند.
            $activeClinicIds = $doctor->address()
                ->where('is_active', true)
                ->lockForUpdate()
                ->pluck('addresses.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($data->clinicId !== null) {
                $this->ensureClinic->handle($doctor, $data->clinicId);
                $activeClinicIds = array_values(array_intersect(
                    $activeClinicIds,
                    [$data->clinicId]
                ));
            }

            $workingHours = DoctorWorkingHour::query()
                ->where('doctor_id', $doctor->id)
                ->where('is_active', true)
                ->whereIn('clinic_id', $activeClinicIds)
                ->get();

            if ($workingHours->isEmpty()) {
                return;
            }

            $holidayKeys = DoctorHoliday::query()
                ->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $activeClinicIds)
                ->whereBetween('holiday_date', [
                    $baseDate->toDateString(),
                    $baseDate->copy()->addWeeks($data->weeksCount)->toDateString(),
                ])
                ->get(['clinic_id', 'holiday_date'])
                ->mapWithKeys(fn (DoctorHoliday $holiday) => [
                    $holiday->clinic_id . '|' . $holiday->holiday_date->format('Y-m-d') => true,
                ]);

            $hasWorkingHours = true;

            for ($week = 0; $week < $data->weeksCount; $week++) {
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

                    if ($duration < 1 || $end->lessThanOrEqualTo($start)) {
                        $skippedCount++;
                        continue;
                    }

                    if ($holidayKeys->has($workingHour->clinic_id . '|' . $date)) {
                        $skippedCount += (int) floor($start->diffInMinutes($end) / $duration);
                        continue;
                    }

                    $cursor = $start->copy();

                    while ($cursor->copy()->addMinutes($duration)->lte($end)) {
                        $slotEnd = $cursor->copy()->addMinutes($duration);
                        $startValue = $cursor->format('H:i:s');
                        $endValue = $slotEnd->format('H:i:s');

                        // Do not create slots whose start time has already passed today.
                        if ($cursor->lessThanOrEqualTo(now())) {
                            $skippedCount++;
                            $cursor->addMinutes($duration);
                            continue;
                        }

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
            'has_working_hours' => $hasWorkingHours,
        ];
    }
}
