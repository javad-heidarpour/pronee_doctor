<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('archived_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 32);
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('original_name', 255);
            $table->string('disk', 32)->default('local');
            $table->string('path', 500)->unique();
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size');
            $table->char('checksum_sha256', 64);
            $table->timestamp('uploaded_at');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'patient_id', 'clinic_id', 'archived_at', 'uploaded_at'], 'patient_documents_scope_idx');
            $table->index(['patient_id', 'category', 'uploaded_at'], 'patient_documents_category_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_documents');
    }
};
