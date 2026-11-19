<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinic_staff_work_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('staff_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->unsignedSmallInteger('grace_minutes')->default(10);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id', 'clinic_id', 'staff_user_id', 'weekday', 'is_active'], 'staff_schedule_lookup_idx');
        });

        Schema::create('clinic_staff_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('staff_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('work_date');
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->string('status', 24)->default('present');
            $table->unsignedSmallInteger('minutes_late')->default(0);
            $table->unsignedSmallInteger('minutes_worked')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'clinic_id', 'staff_user_id', 'work_date'], 'staff_attendance_daily_unique');
            $table->index(['organization_id', 'work_date', 'status'], 'staff_attendance_report_idx');
        });

        Schema::create('clinic_staff_attendance_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendance_id')->constrained('clinic_staff_attendances')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('staff_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 32);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('reason', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['attendance_id', 'created_at'], 'staff_attendance_events_idx');
        });

        if (Schema::hasTable('menus')) {
            DB::table('menus')->updateOrInsert(
                ['route_name' => 'portal.staff-attendance.index'],
                [
                    'title' => 'حضور و غیاب کارکنان',
                    'icon' => 'IconCalendarClock',
                    'order' => 60,
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
            DB::table('menus')->where('route_name', 'portal.staff-attendance.index')->delete();
        }
        Schema::dropIfExists('clinic_staff_attendance_events');
        Schema::dropIfExists('clinic_staff_attendances');
        Schema::dropIfExists('clinic_staff_work_schedules');
    }
};
