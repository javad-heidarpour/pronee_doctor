<?php

declare(strict_types=1);

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinic_staff_responsibilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('staff_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('description');
            $table->string('priority', 16)->default('normal');
            $table->string('status', 16)->default('active');
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->date('review_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_id', 'clinic_id', 'staff_user_id', 'status'], 'staff_responsibility_scope_idx');
            $table->index(['organization_id', 'effective_from', 'effective_until'], 'staff_responsibility_dates_idx');
        });

        Schema::create('clinic_staff_responsibility_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('responsibility_id')->constrained('clinic_staff_responsibilities')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 24);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['responsibility_id', 'created_at'], 'staff_responsibility_events_idx');
        });

        if (Schema::hasTable('menus')) {
            DB::table('menus')->updateOrInsert(
                ['route_name' => 'portal.staff-responsibilities.index'],
                [
                    'title' => 'شرح وظایف و مسئولیت‌ها',
                    'icon' => 'IconClipboardList',
                    'order' => 61,
                    'guard' => 'doctor',
                    'is_active' => true,
                    'show_in_sidebar' => true,
                    'permissions' => json_encode(['staff.attendance.view']),
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
            DB::table('menus')->where('route_name', 'portal.staff-responsibilities.index')->delete();
        }

        Schema::dropIfExists('clinic_staff_responsibility_events');
        Schema::dropIfExists('clinic_staff_responsibilities');
    }
};
