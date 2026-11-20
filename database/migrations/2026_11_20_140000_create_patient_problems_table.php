<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_problems', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained('patient_encounters')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 32)->default('diagnosis');
            $table->string('title', 180);
            $table->string('code', 64)->nullable();
            $table->string('code_system', 32)->default('local');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('severity', 20)->nullable();
            $table->date('onset_date')->nullable();
            $table->date('resolved_on')->nullable();
            $table->string('source', 32)->default('clinician_assessed');
            $table->timestamps();

            $table->index(['doctor_id', 'patient_id', 'status'], 'patient_problems_owner_status_idx');
            $table->index(['patient_id', 'category', 'status'], 'patient_problems_category_status_idx');
            $table->index(['doctor_id', 'code_system', 'code'], 'patient_problems_code_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_problems');
    }
};