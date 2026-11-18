<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_follow_up_tasks', function (Blueprint $table): void {
            $table->foreignId('collateral_id')->nullable()->after('treatment_plan_id')
                ->constrained('patient_collaterals')->nullOnDelete();
            $table->index(['collateral_id', 'status'], 'followup_collateral_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('patient_follow_up_tasks', function (Blueprint $table): void {
            $table->dropIndex('followup_collateral_status_idx');
            $table->dropConstrainedForeignId('collateral_id');
        });
    }
};
