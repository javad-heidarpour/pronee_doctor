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
}