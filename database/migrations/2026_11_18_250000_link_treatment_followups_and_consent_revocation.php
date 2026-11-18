<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_treatment_plans', function (Blueprint $table): void {
            $table->timestamp('sms_consent_revoked_at')->nullable();
            $table->timestamp('follow_up_sms_consent_revoked_at')->nullable();
        });

        Schema::table('patient_follow_up_tasks', function (Blueprint $table): void {
            $table->foreignId('treatment_plan_id')->nullable()->after('appointment_id')
                ->constrained('patient_treatment_plans')->nullOnDelete();
            $table->index(['treatment_plan_id', 'status'], 'followup_treatment_plan_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('patient_follow_up_tasks', function (Blueprint $table): void {
            $table->dropIndex('followup_treatment_plan_status_idx');
            $table->dropConstrainedForeignId('treatment_plan_id');
        });
        Schema::table('patient_treatment_plans', function (Blueprint $table): void {
            $table->dropColumn(['sms_consent_revoked_at', 'follow_up_sms_consent_revoked_at']);
        });
    }
};
