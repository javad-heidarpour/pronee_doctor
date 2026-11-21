<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->string('attendance_outcome', 20)->nullable()->after('status');
            $table->timestamp('attendance_outcome_recorded_at')->nullable()->after('attendance_outcome');
            $table->foreignId('attendance_outcome_recorded_by')->nullable()->after('attendance_outcome_recorded_at')
                ->constrained('users')->nullOnDelete();
            $table->index(['doctor_id', 'patient_id', 'attendance_outcome', 'appointment_date'], 'appt_attendance_learning_idx');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex('appt_attendance_learning_idx');
            $table->dropConstrainedForeignId('attendance_outcome_recorded_by');
            $table->dropColumn(['attendance_outcome', 'attendance_outcome_recorded_at']);
        });
    }
};
