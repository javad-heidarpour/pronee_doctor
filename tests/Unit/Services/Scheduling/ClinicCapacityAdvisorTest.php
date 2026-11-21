<?php
declare(strict_types=1);
namespace Tests\Unit\Services\Scheduling;
use App\Services\Scheduling\ClinicCapacityAdvisor;
use PHPUnit\Framework\TestCase;
final class ClinicCapacityAdvisorTest extends TestCase
{
    public function test_calculates_occupancy_and_excludes_cancelled_slots_from_capacity(): void
    {
        $result=(new ClinicCapacityAdvisor())->summarize([
            $this->slot('2026-11-16','booked','confirmed'),
            $this->slot('2026-11-16','available',null),
            $this->slot('2026-11-16','cancelled',null),
        ]);
        self::assertSame(2,$result['summary']['capacity']);
        self::assertSame(1,$result['summary']['occupied']);
        self::assertSame(1,$result['summary']['available']);
        self::assertSame(1,$result['summary']['cancelled']);
        self::assertSame(50,$result['summary']['utilization_percent']);
        self::assertSame(60,$result['summary']['duration_minutes']);
    }
    public function test_active_appointment_counts_as_occupied_when_slot_status_is_stale(): void
    {
        $result=(new ClinicCapacityAdvisor())->summarize([
            $this->slot('2026-11-16','available','pending'),
            $this->slot('2026-11-16','available','cancelled'),
        ]);
        self::assertSame(1,$result['summary']['occupied']);
        self::assertSame(1,$result['summary']['available']);
    }
    public function test_weekday_recommendation_requires_four_observations_and_flags_high_demand(): void
    {
        $slots=[];
        foreach(['2026-11-02','2026-11-09'] as $date) for($i=0;$i<4;$i++) $slots[]=$this->slot($date,'booked','confirmed');
        $result=(new ClinicCapacityAdvisor())->summarize($slots);
        self::assertCount(1,$result['weekday_recommendations']);
        self::assertSame('high_demand',$result['weekday_recommendations'][0]['signal']);
        self::assertSame(100,$result['weekday_recommendations'][0]['utilization_percent']);
    }
    public function test_cancelled_appointment_does_not_consume_capacity_even_if_slot_flag_is_stale(): void
    {
        $result=(new ClinicCapacityAdvisor())->summarize([
            $this->slot('2026-11-16','booked','cancelled'),
        ]);
        self::assertSame(0,$result['summary']['occupied']);
        self::assertSame(1,$result['summary']['available']);
    }
    public function test_one_observed_day_is_not_enough_for_weekday_recommendation(): void
    {
        $slots=[];
        for($i=0;$i<8;$i++) $slots[]=$this->slot('2026-11-02','booked','confirmed');
        $result=(new ClinicCapacityAdvisor())->summarize($slots);
        self::assertSame('insufficient',$result['weekday_recommendations'][0]['signal']);
        self::assertSame(1,$result['weekday_recommendations'][0]['observed_days']);
    }
    public function test_empty_history_returns_zero_utilization(): void
    {
        $result=(new ClinicCapacityAdvisor())->summarize([]);
        self::assertSame(0,$result['summary']['capacity']);
        self::assertSame(0,$result['summary']['utilization_percent']);
        self::assertSame([],$result['daily']);
        self::assertSame([],$result['weekday_recommendations']);
    }
    private function slot(string $date,string $status,?string $appointmentStatus):array
    {
        return ['clinic_id'=>7,'clinic_name'=>'شعبه مرکزی','date'=>$date,'start_time'=>'09:00:00',
            'end_time'=>'09:30:00','status'=>$status,'appointment_status'=>$appointmentStatus];
    }
}