<?php

declare(strict_types=1);

namespace App\Services\Staff;

use Illuminate\Support\Collection;

final class StaffAttendancePerformanceEvaluator
{
    /**
     * Transparent attendance-discipline indicators, not a complete employment
     * appraisal and never a measure of clinical quality or patient outcomes.
     */
    public function evaluate(Collection $attendances, Collection $absences): Collection
    {
        $groups = $attendances->groupBy(fn ($row) => $row->organization_id.'|'.$row->clinic_id.'|'.$row->staff_user_id);
        $absenceGroups = $absences->groupBy(fn ($row) => $row['organization_id'].'|'.$row['clinic_id'].'|'.$row['staff_user_id']);

        return $groups->keys()->merge($absenceGroups->keys())->unique()->map(function (string $key) use ($groups, $absenceGroups): array {
            $rows = $groups->get($key, collect());
            $missing = $absenceGroups->get($key, collect());
            $first = $rows->first() ?? (object) $missing->first();
            $recorded = $rows->count();
            $late = $rows->where('minutes_late', '>', 0)->count();
            $incomplete = $rows->whereNull('check_out_at')->count();
            $absent = $missing->count();
            $scheduled = $recorded + $absent;
            $sampleSufficient = $scheduled >= 5;

            $lateRate = $recorded > 0 ? $late / $recorded : 0.0;
            $incompleteRate = $recorded > 0 ? $incomplete / $recorded : 0.0;
            $absenceRate = $scheduled > 0 ? $absent / $scheduled : 0.0;
            $score = $sampleSufficient
                ? (int) round(max(0, min(100, 100 - ($lateRate * 25) - ($incompleteRate * 20) - ($absenceRate * 40))))
                : null;

            return [
                'organization_id' => (int) $first->organization_id,
                'clinic_id' => (int) $first->clinic_id,
                'staff_user_id' => (int) $first->staff_user_id,
                'staff_name' => trim(($first->staff_name ?? '').' '.($first->staff_family ?? '')),
                'clinic_name' => $first->clinic_name ?? '',
                'days_recorded' => $recorded,
                'late_days' => $late,
                'incomplete_days' => $incomplete,
                'absent_days' => $absent,
                'scheduled_days' => $scheduled,
                'worked_minutes' => (int) $rows->sum('minutes_worked'),
                'score' => $score,
                'sample_sufficient' => $sampleSufficient,
                'scope' => 'attendance_discipline_only',
                'limitations' => [
                    'not_a_complete_employment_appraisal',
                    'not_a_measure_of_clinical_quality_or_patient_outcomes',
                    'leave_and_holiday_exclusions_follow_recorded_schedule_data',
                    'small_samples_are_not_scored',
                ],
            ];
        })->sortBy(fn ($row) => [$row['score'] === null ? 1 : 0, $row['score'] ?? 0, $row['staff_name']])->values();
    }
}
