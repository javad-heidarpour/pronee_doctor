<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_encounters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->restrictOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note_type', 32)->default('consultation');
            $table->string('status', 16)->default('draft');
            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();
            $table->string('diagnosis', 500)->nullable();
            $table->string('diagnosis_code', 40)->nullable();
            $table->boolean('reviewed_allergies')->default(false);
            $table->boolean('reviewed_medications')->default(false);
            $table->boolean('reviewed_vitals')->default(false);
            $table->json('clinical_snapshot')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'patient_id', 'created_at'], 'patient_encounters_doctor_patient_date_idx');
            $table->index(['appointment_id', 'status'], 'patient_encounters_appointment_status_idx');
            $table->index(['clinic_id', 'created_at'], 'patient_encounters_clinic_date_idx');
        });

        Schema::create('patient_encounter_prescriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('encounter_id')->constrained('patient_encounters')->cascadeOnDelete();
            $table->string('medication_name', 180);
            $table->string('strength', 120)->nullable();
            $table->string('dosage', 120);
            $table->string('route', 80);
            $table->string('frequency', 120);
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->string('quantity', 60)->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['encounter_id', 'sort_order'], 'encounter_prescriptions_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_encounter_prescriptions');
        Schema::dropIfExists('patient_encounters');
    }
};