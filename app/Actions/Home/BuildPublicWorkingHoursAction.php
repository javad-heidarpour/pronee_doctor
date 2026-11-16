<?php

namespace App\\Actions\\Home;

use App\\Models\\Doctor;
use App\\Models\\DoctorWorkingHour;

class BuildPublicWorkingHoursAction
{
    public function handle(Doctor $doctor)
    {
        $workingHours = DoctorWorkingHour::with('clinic')
            ->where('doctor_id', $doctor->id)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy(function ($hour) {
                return $hour->day_of_week . '_' . $hour->clinic_id;
            })
            ->map(function ($hours) {
                $firstHour = $hours->first();

                return [
                    'day_of_week' => $firstHour->day_of_week,
                    'day_name' => $firstHour->day_name,
                    'clinic_id' => $firstHour->clinic_id,
                    'clinic' => $firstHour->clinic?->name,
                    'hours' => $hours
                        ->unique(function ($item) {
                            return $item->start_time . '_' . $item->end_time;
                        })
                        ->map(function ($hour) {
                            return $hour->formatted_start_time
                                . ' - '
                                . $hour->formatted_end_time;
                        })
                        ->implode(' / '),
                ];
            })
            ->sortBy('day_of_week')
            ->values();

        $allDays = collect([
            ['day_of_week' => 0, 'day_name' => 'شنبه'],
            ['day_of_week' => 1, 'day_name' => 'یکشنبه'],
            ['day_of_week' => 2, 'day_name' => 'دوشنبه'],
            ['day_of_week' => 3, 'day_name' => 'سه‌شنبه'],
            ['day_of_week' => 4, 'day_name' => 'چهارشنبه'],
            ['day_of_week' => 5, 'day_name' => 'پنجشنبه'],
            ['day_of_week' => 6, 'day_name' => 'جمعه'],
        ]);

        return $allDays->map(function ($day) use ($workingHours) {
            $exist = $workingHours->firstWhere(
                'day_of_week',
                $day['day_of_week']
            );

            return [
                'name' => $day['day_name'],
                'hours' => $exist ? $exist['hours'] : null,
                'clinic' => $exist ? $exist['clinic'] : null,
            ];
        });
    }
}
