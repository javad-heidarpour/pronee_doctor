<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_brain_waitlist_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('preferred_from');
            $table->date('preferred_until');
            $table->string('preferred_period', 16)->default('any');
            $table->boolean('contact_consent')->default(false);
            $table->string('status', 16)->default('waiting');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'status', 'preferred_from'], 'clinic_brain_waitlist_scope_idx');
            $table->index(['patient_id', 'status'], 'clinic_brain_waitlist_patient_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_brain_waitlist_entries');
    }
};
