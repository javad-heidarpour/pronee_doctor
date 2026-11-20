<?php

declare(strict_types=1);

namespace Tests\Unit\Home;

use App\Actions\Home\ListPublicMedicalServicesAction;
use App\Actions\Home\ListPublicSurgicalProceduresAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ListPublicCatalogActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_medical_services_include_only_active_safe_fields(): void
    {
        $activeId = DB::table('medical_services')->insertGetId([
            'title' => 'مشاوره پزشکی',
            'slug' => 'medical-consultation-test',
            'category' => 'general_surgery',
            'icon_html' => '<svg onload="alert(1)"></svg>',
            'short_description' => 'توضیح عمومی',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('medical_services')->insert([
            'title' => 'خدمت غیرفعال',
            'slug' => 'inactive-service-test',
            'category' => 'general_surgery',
            'is_active' => false,
            'sort_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $services = app(ListPublicMedicalServicesAction::class)->handle();

        $this->assertCount(1, $services);
        $this->assertSame($activeId, $services->first()->id);
        $this->assertSame('مشاوره پزشکی', $services->first()->title);
        $this->assertArrayNotHasKey('icon_html', $services->first()->getAttributes());
        $this->assertArrayNotHasKey('meta_keywords', $services->first()->getAttributes());
    }

    public function test_public_surgical_procedures_include_only_active_safe_fields(): void
    {
        $activeId = DB::table('surgical_procedures')->insertGetId([
            'title' => 'جراحی نمونه',
            'slug' => 'sample-surgery-test',
            'category' => 'general_surgery',
            'icon_html' => '<svg onload="alert(1)"></svg>',
            'short_description' => 'توضیح عمومی',
            'intro_title' => 'معرفی جراحی',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('surgical_procedures')->insert([
            'title' => 'جراحی غیرفعال',
            'slug' => 'inactive-surgery-test',
            'category' => 'general_surgery',
            'is_active' => false,
            'sort_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $procedures = app(ListPublicSurgicalProceduresAction::class)->handle();

        $this->assertCount(1, $procedures);
        $this->assertSame($activeId, $procedures->first()->id);
        $this->assertSame('جراحی نمونه', $procedures->first()->title);
        $this->assertSame('معرفی جراحی', $procedures->first()->intro_title);
        $this->assertArrayNotHasKey('icon_html', $procedures->first()->getAttributes());
        $this->assertArrayNotHasKey('meta_keywords', $procedures->first()->getAttributes());
    }
}
