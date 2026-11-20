<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinic_discount_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id', 'cdc_doctor_fk')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id', 'cdc_clinic_fk')->nullable()->constrained('addresses')->nullOnDelete();
            $table->foreignId('created_by_user_id', 'cdc_creator_fk')->constrained('users')->restrictOnDelete();
            $table->string('code', 32);
            $table->string('name', 160);
            $table->string('discount_type', 12);
            $table->unsignedBigInteger('discount_value');
            $table->unsignedBigInteger('minimum_amount_rial')->default(0);
            $table->unsignedBigInteger('maximum_discount_rial')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedSmallInteger('per_patient_limit')->default(1);
            $table->string('status', 16)->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->unique(['doctor_id', 'code'], 'cdc_doctor_code_unique');
            $table->index(['doctor_id', 'status', 'starts_at', 'ends_at'], 'cdc_active_window_idx');
        });

        Schema::create('clinic_discount_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('discount_code_id', 'cdr_code_fk')->constrained('clinic_discount_codes')->restrictOnDelete();
            $table->foreignId('appointment_id', 'cdr_appointment_fk')->unique()->constrained('appointments')->restrictOnDelete();
            $table->foreignId('patient_id', 'cdr_patient_fk')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('doctor_id', 'cdr_doctor_fk')->constrained('doctors')->cascadeOnDelete();
            $table->unsignedBigInteger('original_amount_rial');
            $table->unsignedBigInteger('discount_amount_rial');
            $table->unsignedBigInteger('final_amount_rial');
            $table->timestamp('applied_at');
            $table->timestamps();
            $table->index(['discount_code_id', 'patient_id'], 'cdr_code_patient_idx');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('discount_code_id', 'appointments_discount_code_fk')->nullable()
                ->constrained('clinic_discount_codes')->nullOnDelete();
            $table->unsignedBigInteger('original_fee_rial')->nullable();
            $table->unsignedBigInteger('discount_amount_rial')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign('appointments_discount_code_fk');
            $table->dropColumn(['discount_code_id', 'original_fee_rial', 'discount_amount_rial']);
        });
        Schema::dropIfExists('clinic_discount_redemptions');
        Schema::dropIfExists('clinic_discount_codes');
    }
};
