<?php
declare(strict_types=1);
namespace Tests\Unit\Services\PatientRetention;
use App\Services\PatientRetention\RetentionSegment;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
final class RetentionSegmentTest extends TestCase {
 public function test_upcoming_appointment_takes_precedence(): void {
  self::assertSame('scheduled',(new RetentionSegment())->classify(Carbon::parse('2026-01-01'),true,false,Carbon::parse('2026-11-18'))['key']);
 }
 public function test_open_follow_up_prevents_win_back_classification(): void {
  self::assertSame('follow_up',(new RetentionSegment())->classify(Carbon::parse('2026-01-01'),false,true,Carbon::parse('2026-11-18'))['key']);
 }
 public function test_configured_inactivity_threshold_is_applied(): void {
  $result=(new RetentionSegment())->classify(Carbon::parse('2026-08-20'),false,false,Carbon::parse('2026-11-18'),90);
  self::assertSame('win_back',$result['key']); self::assertSame(90,$result['days_inactive']);
 }
 public function test_missing_history_is_unknown(): void {
  $result=(new RetentionSegment())->classify(null,false,false,Carbon::parse('2026-11-18'));
  self::assertSame('unknown',$result['key']); self::assertNull($result['days_inactive']);
 }
}