<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('signed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('consent_type', 32);
            $table->string('title', 180);
            $table->longText('consent_text');
            $table->char('content_sha256', 64);
            $table->string('status', 16)->default('pending');
            $table->string('signer_name', 180)->nullable();
            $table->string('signature_method', 32)->nullable();
            $table->char('signature_sha256', 64)->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signer_ip_address', 45)->nullable();
            $table->string('signer_user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'patient_id', 'clinic_id', 'status'], 'patient_consents_scope_status_idx');
            $table->index(['patient_id', 'status', 'created_at'], 'patient_consents_patient_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_consents');
    }
};
