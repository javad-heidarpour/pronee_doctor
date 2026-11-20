<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Models\Doctor;
use App\Models\DoctorHoliday;
use App\Models\DoctorWorkingHour;

final class BuildPublicWorkingHoursAction
{
    public function handle(Doctor $doctor): array
    {
        $clinics = $doctor->address()
            ->where('is_active', true)
            ->with('city')
            ->orderBy('id')
            ->get();

        $workingHours = DoctorWorkingHour::query()
            ->with('clinic')
            ->where('doctor_id', $doctor->id)
            ->where('is_active', true)
            ->whereIn('clinic_id', $clinics->pluck('id'))
            ->whereHas('clinic', fn ($query) => $query->where('is_active', true))
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('clinic_id');

        $allDays = collect([
            ['day_of_week' => 0, 'day_name' => 'شنبه'],
            ['day_of_week' => 1, 'day_name' => 'یکشنبه'],
            ['day_of_week' => 2, 'day_name' => 'دوشنبه'],
            ['day_of_week' => 3, 'day_name' => 'سه‌شنبه'],
            ['day_of_week' => 4, 'day_name' => 'چهارشنبه'],
            ['day_of_week' => 5, 'day_name' => 'پنجشنبه'],
            ['day_of_week' => 6, 'day_name' => 'جمعه'],
        ]);

        $clinicSchedules = $clinics->map(function ($clinic) use ($workingHours, $allDays): array {
            $clinicHours = $workingHours->get($clinic->id, collect());

            $days = $allDays->map(function (array $day) use ($clinicHours): array {
                $hours = $clinicHours
                    ->where('day_of_week', $day['day_of_week'])
                    ->unique(fn ($item) => $item->start_time . '_' . $item->end_time)
                    ->map(fn ($hour) => $hour->formatted_start_time . ' - ' . $hour->formatted_end_time)
                    ->values();

                return [
                    'day_of_week' => $day['day_of_week'],
                    'name' => $day['day_name'],
                    'hours' => $hours->isNotEmpty() ? $hours->implode(' / ') : null,
                ];
            })->values();

            return [
                'clinic_id' => $clinic->id,
                'clinic' => $clinic->name,
                'city' => $clinic->city?->name,
                'days' => $days,
            ];
        })->values();

        $holidays = DoctorHoliday::query()
            ->where('doctor_id', $doctor->id)
            ->whereDate('holiday_date', '>=', today()->toDateString())
            ->whereDate('holiday_date', '<=', today()->addDays(180)->toDateString())
            ->whereIn('clinic_id', $clinics->pluck('id'))
            ->with('clinic')
            ->orderBy('holiday_date')
            ->get()
            ->map(fn (DoctorHoliday $holiday): array => [
                'id' => $holiday->id,
                'date' => $holiday->holiday_date->format('Y-m-d'),
                'title' => $holiday->title,
                'clinic' => $holiday->clinic?->name,
            ])
            ->values();

        return [
            'clinics' => $clinicSchedules,
            'holidays' => $holidays,
        ];
    }
}
