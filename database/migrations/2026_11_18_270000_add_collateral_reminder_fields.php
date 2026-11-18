<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_collaterals', function (Blueprint $table): void {
            $table->boolean('reminder_enabled')->default(true);
            $table->dateTime('last_reminded_at')->nullable();
            $table->index(['status', 'reminder_enabled', 'due_date'], 'collateral_reminder_due_idx');
        });
    }

    public function down(): void
    {
        Schema::table('patient_collaterals', function (Blueprint $table): void {
            $table->dropIndex('collateral_reminder_due_idx');
            $table->dropColumn(['reminder_enabled', 'last_reminded_at']);
        });
    }
};
