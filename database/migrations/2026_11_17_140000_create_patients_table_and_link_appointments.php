<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 45)->nullable();
            $table->string('national_code', 20)->nullable();
            $table->timestamps();

            // Patient identity is scoped to a doctor. National code is the
            // primary identifier; phone is a fallback only when it is absent.
            $table->index(['doctor_id', 'national_code'], 'patients_doctor_national_code_index');
            $table->index(['doctor_id', 'phone'], 'patients_doctor_phone_index');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignId('patient_id')
                ->nullable()
                ->after('doctor_id')
                ->constrained('patients')
                ->nullOnDelete();

            $table->index(['doctor_id', 'patient_id', 'appointment_date'], 'appointments_doctor_patient_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex('appointments_doctor_patient_date_index');
            $table->dropConstrainedForeignId('patient_id');
        });

        Schema::dropIfExists('patients');
    }
};
