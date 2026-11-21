<?php
declare(strict_types=1);
namespace Tests\Unit\Services;
use App\Services\ClinicalDecisionSupport;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
final class ClinicalDecisionSupportTest extends TestCase {
 public function test_missing_data_is_unknown_not_normal(): void {$r=(new ClinicalDecisionSupport())->evaluate([],Carbon::parse('2026-11-18 12:00'));self::assertSame(3,$r['review_count']);self::assertSame(['unknown','unknown','unknown'],array_column($r['checks'],'status'));}
 public function test_recent_context_is_available_without_claiming_safety(): void {$r=(new ClinicalDecisionSupport())->evaluate(['allergies'=>[['title'=>'x']],'medications'=>[['title'=>'y']],'vitals'=>[['measured_at'=>'2026-11-18 11:00']]],Carbon::parse('2026-11-18 12:00'));self::assertSame(0,$r['review_count']);self::assertContains('no_drug_interaction_check',$r['limitations']);}
 public function test_old_vitals_are_stale(): void {$r=(new ClinicalDecisionSupport())->evaluate(['vitals'=>[['measured_at'=>'2026-11-16 00:00']]],Carbon::parse('2026-11-18 12:00'));self::assertSame('stale',$r['checks'][2]['status']);}
 public function test_future_dated_vitals_are_unknown_and_require_timestamp_review(): void {$r=(new ClinicalDecisionSupport())->evaluate(['vitals'=>[['measured_at'=>'2026-11-19 12:00']]],Carbon::parse('2026-11-18 12:00'));self::assertSame('unknown',$r['checks'][2]['status']);self::assertStringContainsString('آینده',$r['checks'][2]['message']);}
 public function test_available_context_does_not_claim_patient_is_safe(): void {$r=(new ClinicalDecisionSupport())->evaluate(['allergies'=>[['title'=>'x']],'medications'=>[['title'=>'y']],'vitals'=>[['measured_at'=>'2026-11-18 11:00']]],Carbon::parse('2026-11-18 12:00'));self::assertContains('availability_does_not_mean_normal',$r['limitations']);self::assertStringContainsString('طبیعی‌بودن',$r['summary']);}

}