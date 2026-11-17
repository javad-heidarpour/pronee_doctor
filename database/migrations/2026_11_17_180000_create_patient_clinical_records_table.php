<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_clinical_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->string('kind', 32);
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('dosage', 120)->nullable();
            $table->string('frequency', 120)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'kind', 'is_active'], 'patient_clinical_patient_kind_active_index');
            $table->index(['doctor_id', 'patient_id'], 'patient_clinical_doctor_patient_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_clinical_records');
    }
};
