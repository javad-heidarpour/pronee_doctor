<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Patient\PhysicianWorkflowAssistant;
use Carbon\Carbon;
use Tests\TestCase;

final class PhysicianWorkflowAssistantTest extends TestCase
{
    public function test_it_suggests_only_transparent_workflow_tasks_from_missing_and_stale_data(): void
    {
        $assistant = new PhysicianWorkflowAssistant();
        $result = $assistant->suggest([
            'allergies'=>[],
            'medications'=>[],
            'latest_vitals'=>[['type'=>'blood_pressure','measured_at'=>'2026-11-18T08:00:00+03:30']],
            'open_follow_ups'=>[['title'=>'پیگیری','due_at'=>'2026-11-19T08:00:00+03:30']],
        ],Carbon::parse('2026-11-20 12:00:00'));

        $keys = collect($result['tasks'])->pluck('key')->all();
        $this->assertContains('verify_allergies',$keys);
        $this->assertContains('reconcile_medications',$keys);
        $this->assertContains('refresh_vitals',$keys);
        $this->assertContains('review_overdue_followups',$keys);
        $this->assertSame('transparent_rules',$result['mode']);
        $this->assertStringContainsString('تشخیص',$result['disclaimer']);
    }

    public function test_it_does_not_generate_missing_data_tasks_when_recorded_data_is_available(): void
    {
        $result = app(PhysicianWorkflowAssistant::class)->suggest([
            'allergies'=>[['title'=>'حساسیت ثبت‌شده']],
            'medications'=>[['title'=>'داروی ثبت‌شده']],
            'latest_vitals'=>[['type'=>'weight','measured_at'=>'2026-11-20T11:00:00+03:30']],
            'open_follow_ups'=>[],
        ],Carbon::parse('2026-11-20 12:00:00'));

        $this->assertSame([],$result['tasks']);
    }
}
