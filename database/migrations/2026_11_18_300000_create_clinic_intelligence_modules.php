<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clinic_inventory_items', function (Blueprint $table): void {
            $table->unsignedSmallInteger('lead_time_days')->default(7);
            $table->unsignedSmallInteger('safety_stock_days')->default(7);
            $table->string('preferred_supplier', 180)->nullable();
            $table->index(['clinic_id', 'is_active', 'current_quantity'], 'inventory_forecast_scope_idx');
        });

        Schema::create('clinic_health_programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->string('title', 180);
            $table->string('category', 40);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('interval_days')->default(365);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['doctor_id', 'is_active', 'category']);
        });

        Schema::create('clinic_health_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('clinic_health_programs')->cascadeOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamp('consent_at');
            $table->foreignId('consent_recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('last_reminded_at')->nullable();
            $table->timestamps();
            $table->unique(['patient_id', 'program_id'], 'health_membership_patient_program_unique');
            $table->index(['doctor_id', 'status', 'enrolled_at']);
        });

        Schema::create('clinic_health_points', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('membership_id')->constrained('clinic_health_memberships')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->unsignedSmallInteger('points');
            $table->string('activity_key', 48);
            $table->string('description', 180);
            $table->string('source_key', 120);
            $table->foreignId('awarded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['membership_id', 'source_key'], 'health_points_source_unique');
            $table->index(['patient_id', 'created_at']);
        });

        Schema::create('clinic_economic_scenarios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('scenario_type', 32);
            $table->string('name', 180);
            $table->json('inputs');
            $table->json('results');
            $table->timestamps();
            $table->index(['doctor_id', 'scenario_type', 'created_at']);
        });

        if (Schema::hasTable('menus')) {
            DB::table('menus')->updateOrInsert(
                ['route_name' => 'portal.intelligence.index'],
                [
                    'title' => 'هوشمندی کلینیک',
                    'icon' => 'IconBrainCircuit',
                    'order' => 57,
                    'guard' => 'doctor',
                    'is_active' => true,
                    'show_in_sidebar' => true,
                    'permissions' => json_encode(['inventory.view', 'inventory.manage', 'patients.view', 'patients.manage']),
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
            DB::table('menus')->where('route_name', 'portal.intelligence.index')->delete();
        }
        Schema::dropIfExists('clinic_economic_scenarios');
        Schema::dropIfExists('clinic_health_points');
        Schema::dropIfExists('clinic_health_memberships');
        Schema::dropIfExists('clinic_health_programs');
        Schema::table('clinic_inventory_items', function (Blueprint $table): void {
            $table->dropIndex('inventory_forecast_scope_idx');
            $table->dropColumn(['lead_time_days', 'safety_stock_days', 'preferred_supplier']);
        });
    }
};
