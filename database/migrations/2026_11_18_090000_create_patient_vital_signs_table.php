<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_vital_signs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->string('measurement_type', 40);
            $table->decimal('value', 8, 2);
            $table->string('unit', 16);
            $table->dateTime('measured_at');
            $table->string('note', 1000)->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'patient_id', 'measured_at'], 'patient_vitals_doctor_patient_date_index');
            $table->index(['patient_id', 'measurement_type', 'measured_at'], 'patient_vitals_type_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_vital_signs');
    }
};
