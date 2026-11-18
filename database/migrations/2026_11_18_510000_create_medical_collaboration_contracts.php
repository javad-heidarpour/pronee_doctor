<?php

declare(strict_types=1);

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_collaboration_contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->foreignId('provider_id')->constrained('medical_network_providers')->restrictOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('service_type', 32);
            $table->unsignedBigInteger('unit_fee_rial');
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->string('status', 20)->default('proposed');
            $table->timestamp('accepted_at')->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'status', 'starts_at', 'ends_at'], 'med_contract_doctor_status_dates');
            $table->index(['provider_id', 'status'], 'med_contract_provider_status');
        });

        Schema::create('medical_referral_settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->constrained('medical_collaboration_contracts')->restrictOnDelete();
            $table->foreignId('referral_id')->unique()->constrained('medical_referrals')->restrictOnDelete();
            $table->unsignedBigInteger('amount_rial');
            $table->string('status', 16)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['contract_id', 'status'], 'med_ref_settlement_contract_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_referral_settlements');
        Schema::dropIfExists('medical_collaboration_contracts');
    }
};
