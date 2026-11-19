<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinic_staff_leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('staff_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('leave_type', 24);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('pending');
            $table->text('reason');
            $table->text('decision_note')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'staff_user_id', 'status', 'start_date', 'end_date'], 'staff_leave_scope_dates_idx');
        });

        Schema::create('clinic_organization_holidays', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->date('holiday_date');
            $table->string('name', 160);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'holiday_date'], 'organization_holiday_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_organization_holidays');
        Schema::dropIfExists('clinic_staff_leave_requests');
    }
};
