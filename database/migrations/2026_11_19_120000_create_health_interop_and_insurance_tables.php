<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_insurance_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->string('payer_code', 40);
            $table->string('payer_name', 120)->nullable();
            $table->string('policy_number', 100)->nullable();
            $table->string('member_number', 100)->nullable();
            $table->string('status', 30)->default('not_checked');
            $table->timestamp('checked_at')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('tracking_id', 120)->nullable();
            $table->longText('response_payload')->nullable();
            $table->string('failure_code', 80)->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'patient_id', 'checked_at'], 'insurance_patient_checked_idx');
        });

        Schema::create('health_exchange_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained('patient_encounters')->nullOnDelete();
            $table->string('destination', 40);
            $table->string('event_type', 80);
            $table->char('idempotency_key', 64)->unique();
            $table->string('status', 20)->default('pending');
            $table->longText('payload');
            $table->longText('response_payload')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('remote_tracking_id', 120)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['status', 'available_at'], 'health_exchange_dispatch_idx');
            $table->index(['doctor_id', 'created_at'], 'health_exchange_doctor_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_exchange_messages');
        Schema::dropIfExists('patient_insurance_verifications');
    }
};
