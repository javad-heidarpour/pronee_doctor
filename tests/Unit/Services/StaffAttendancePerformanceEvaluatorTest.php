<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Staff\StaffAttendancePerformanceEvaluator;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class StaffAttendancePerformanceEvaluatorTest extends TestCase
{
    public function test_it_scores_attendance_discipline_using_only_scoped_recorded_metrics(): void
    {
        $attendances = collect([
            (object)['organization_id'=>1,'clinic_id'=>2,'staff_user_id'=>3,'staff_name'=>'Sara','staff_family'=>'Ahmadi','clinic_name'=>'Clinic A','minutes_late'=>10,'check_out_at'=>'2026-11-01 16:00:00','minutes_worked'=>450],
            (object)['organization_id'=>1,'clinic_id'=>2,'staff_user_id'=>3,'staff_name'=>'Sara','staff_family'=>'Ahmadi','clinic_name'=>'Clinic A','minutes_late'=>0,'check_out_at'=>null,'minutes_worked'=>300],
            (object)['organization_id'=>1,'clinic_id'=>2,'staff_user_id'=>3,'staff_name'=>'Sara','staff_family'=>'Ahmadi','clinic_name'=>'Clinic A','minutes_late'=>0,'check_out_at'=>'2026-11-03 16:00:00','minutes_worked'=>480],
            (object)['organization_id'=>1,'clinic_id'=>2,'staff_user_id'=>3,'staff_name'=>'Sara','staff_family'=>'Ahmadi','clinic_name'=>'Clinic A','minutes_late'=>0,'check_out_at'=>'2026-11-04 16:00:00','minutes_worked'=>480],
        ]);
        $absences = collect([[
            'organization_id'=>1,'clinic_id'=>2,'staff_user_id'=>3,'staff_name'=>'Sara','staff_family'=>'Ahmadi','clinic_name'=>'Clinic A',
        ]]);

        $row = app(StaffAttendancePerformanceEvaluator::class)->evaluate($attendances,$absences)->first();

        $this->assertSame(4,$row['days_recorded']);
        $this->assertSame(1,$row['late_days']);
        $this->assertSame(1,$row['incomplete_days']);
        $this->assertSame(1,$row['absent_days']);
        $this->assertSame(5,$row['scheduled_days']);
        $this->assertSame(81,$row['score']);
        $this->assertSame('attendance_discipline_only',$row['scope']);
        $this->assertContains('not_a_complete_employment_appraisal',$row['limitations']);
    }

    public function test_it_does_not_score_small_samples_and_preserves_absence_only_staff_rows(): void
    {
        $absences = collect([[
            'organization_id'=>9,'clinic_id'=>8,'staff_user_id'=>7,'staff_name'=>'Ali','staff_family'=>'Karimi','clinic_name'=>'Clinic B',
        ]]);

        $row = app(StaffAttendancePerformanceEvaluator::class)->evaluate(collect(),$absences)->first();

        $this->assertSame('Ali Karimi',$row['staff_name']);
        $this->assertSame(1,$row['absent_days']);
        $this->assertFalse($row['sample_sufficient']);
        $this->assertNull($row['score']);
    }
}
