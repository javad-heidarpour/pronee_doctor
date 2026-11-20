<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinic_cash_register_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id', 'ccrs_doctor_fk')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id', 'ccrs_clinic_fk')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('opened_by_user_id', 'ccrs_opened_by_fk')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by_user_id', 'ccrs_closed_by_fk')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('opening_balance_rial')->default(0);
            $table->unsignedBigInteger('closing_count_rial')->nullable();
            $table->bigInteger('difference_rial')->nullable();
            $table->string('status', 16)->default('open');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->text('opening_note')->nullable();
            $table->text('closing_note')->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'clinic_id', 'status'], 'ccrs_doctor_clinic_status_idx');
        });

        Schema::create('clinic_cash_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id', 'cct_session_fk')->constrained('clinic_cash_register_sessions')->cascadeOnDelete();
            $table->foreignId('doctor_id', 'cct_doctor_fk')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id', 'cct_clinic_fk')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('created_by_user_id', 'cct_created_by_fk')->constrained('users')->restrictOnDelete();
            $table->string('direction', 8);
            $table->string('category', 32);
            $table->unsignedBigInteger('amount_rial');
            $table->string('description', 500);
            $table->string('idempotency_key', 80)->unique('cct_idempotency_unique');
            $table->timestamps();
            $table->index(['session_id', 'created_at'], 'cct_session_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_cash_transactions');
        Schema::dropIfExists('clinic_cash_register_sessions');
    }
};
