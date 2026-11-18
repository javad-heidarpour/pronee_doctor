<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinic_marketing_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('name', 160);
            $table->string('code', 12)->unique();
            $table->string('channel', 24)->default('social');
            $table->string('status', 20)->default('active');
            $table->unsignedBigInteger('budget_rial')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'status', 'created_at'], 'marketing_campaign_doctor_status_idx');
            $table->index(['clinic_id', 'status'], 'marketing_campaign_clinic_status_idx');
        });

        Schema::create('clinic_marketing_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained('clinic_marketing_campaigns')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->unique()->constrained('appointments')->nullOnDelete();
            $table->string('event_type', 24);
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
            $table->index(['campaign_id', 'event_type', 'occurred_at'], 'marketing_events_campaign_type_date_idx');
        });

        if (Schema::hasTable('menus')) {
            DB::table('menus')->updateOrInsert(
                ['route_name' => 'portal.marketing.index'],
                [
                    'title' => 'رشد و بازاریابی',
                    'icon' => 'IconChartNoAxesCombined',
                    'order' => 56,
                    'guard' => 'doctor',
                    'is_active' => true,
                    'show_in_sidebar' => true,
                    'permissions' => json_encode(['marketing.view', 'marketing.manage']),
                    'roles' => json_encode(['doctor', 'secretary']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('route_name', 'portal.marketing.index')->delete();
        }
        Schema::dropIfExists('clinic_marketing_events');
        Schema::dropIfExists('clinic_marketing_campaigns');
    }
};
