<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_follow_up_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('category', 60)->default('general');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('default_days')->default(3);
            $table->string('contact_method', 20)->default('call');
            $table->boolean('send_sms')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['doctor_id', 'is_active']);
        });

        Schema::create('patient_follow_up_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('patient_follow_up_templates')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('plan_uuid')->nullable();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->dateTime('due_at');
            $table->string('priority', 20)->default('normal');
            $table->string('contact_method', 20)->default('call');
            $table->boolean('send_sms')->default(false);
            $table->string('status', 24)->default('pending');
            $table->string('outcome', 40)->nullable();
            $table->text('result_notes')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('sms_sent_at')->nullable();
            $table->dateTime('sms_attempted_at')->nullable();
            $table->unsignedSmallInteger('sms_attempts')->default(0);
            $table->string('last_sms_error', 500)->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'status', 'due_at']);
            $table->index(['patient_id', 'due_at']);
            $table->index(['assigned_to', 'status', 'due_at']);
            $table->index(['sms_sent_at', 'sms_attempted_at', 'due_at'], 'follow_up_sms_dispatch_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_follow_up_tasks');
        Schema::dropIfExists('patient_follow_up_templates');
    }
};