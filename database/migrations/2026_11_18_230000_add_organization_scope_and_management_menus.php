<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_treatment_plans', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->after('doctor_id')
                ->constrained('clinic_organizations')->nullOnDelete();
            $table->index(['organization_id', 'status']);
        });

        Schema::table('clinic_financial_ledger', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->after('doctor_id')
                ->constrained('clinic_organizations')->nullOnDelete();
        });

        if (Schema::hasTable('menus')) {
            foreach ([
                ['title' => 'مجموعه‌های درمانی', 'route_name' => 'portal.organizations.index', 'icon' => 'IconBuilding2', 'order' => 57, 'permissions' => ['organizations.view'], 'roles' => ['doctor']],
                ['title' => 'عملیات تخصصی کلینیک', 'route_name' => 'portal.operations.index', 'icon' => 'IconBriefcaseMedical', 'order' => 59, 'permissions' => ['services.view'], 'roles' => ['doctor', 'secretary']],
                ['title' => 'مدیریت مالی و اقساط', 'route_name' => 'portal.finance.index', 'icon' => 'IconWallet', 'order' => 58, 'permissions' => ['finance.view'], 'roles' => ['doctor', 'secretary']],
            ] as $menu) {
                if (!DB::table('menus')->where('route_name', $menu['route_name'])->exists()) {
                    DB::table('menus')->insert([
                        ...$menu,
                        'guard' => 'doctor',
                        'is_active' => true,
                        'show_in_sidebar' => true,
                        'permissions' => json_encode($menu['permissions']),
                        'roles' => json_encode($menu['roles']),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('menus')->where('route_name', $menu['route_name'])->update([
                        'permissions' => json_encode($menu['permissions']),
                        'roles' => json_encode($menu['roles']),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->whereIn('route_name', ['portal.organizations.index', 'portal.operations.index'])->delete();
        }
        Schema::table('clinic_financial_ledger', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organization_id');
        });
        Schema::table('patient_treatment_plans', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'status']);
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
