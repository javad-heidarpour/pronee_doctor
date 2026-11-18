<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('patient_medication_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('prescription_id')->constrained('patient_encounter_prescriptions')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->json('reminder_times');
            $table->string('timezone', 64)->default('Asia/Tehran');
            $table->string('status', 16)->default('active');
            $table->timestamps();
            $table->index(['patient_id', 'user_id', 'status'], 'patient_medication_plans_owner_status_idx');
            $table->unique(['user_id', 'prescription_id'], 'patient_medication_plan_prescription_unique');
        });
        Schema::create('patient_medication_doses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained('patient_medication_plans')->cascadeOnDelete();
            $table->dateTime('scheduled_for');
            $table->string('status', 16);
            $table->foreignId('recorded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('recorded_at');
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->unique(['plan_id', 'scheduled_for'], 'patient_medication_dose_slot_unique');
            $table->index(['plan_id', 'scheduled_for', 'status'], 'patient_medication_doses_schedule_idx');
        });
    }
    public function down(): void {
        Schema::dropIfExists('patient_medication_doses');
        Schema::dropIfExists('patient_medication_plans');
    }
};
