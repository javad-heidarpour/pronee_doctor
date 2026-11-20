<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinic_staff_performance_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id', 'cspr_org_fk')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('clinic_id', 'cspr_clinic_fk')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('staff_user_id', 'cspr_staff_fk')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewer_user_id', 'cspr_reviewer_fk')->constrained('users')->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedTinyInteger('reliability_score');
            $table->unsignedTinyInteger('task_completion_score');
            $table->unsignedTinyInteger('teamwork_score');
            $table->unsignedTinyInteger('communication_score');
            $table->unsignedTinyInteger('learning_score');
            $table->text('strengths')->nullable();
            $table->text('improvement_areas')->nullable();
            $table->text('goals')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('employee_response')->nullable();
            $table->timestamps();

            $table->unique(
                ['organization_id', 'clinic_id', 'staff_user_id', 'period_start', 'period_end'],
                'cspr_unique_period'
            );
            $table->index(['organization_id', 'status', 'period_end'], 'cspr_org_status_period_idx');
            $table->index(['staff_user_id', 'status', 'acknowledged_at'], 'cspr_staff_ack_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_staff_performance_reviews');
    }
};
