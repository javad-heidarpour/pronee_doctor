<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_payment_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_payment_id', 'apr_payment_fk')
                ->unique('apr_payment_unique')
                ->constrained('appointment_payments', 'id')
                ->cascadeOnDelete();
            $table->foreignId('requested_by_user_id', 'apr_requested_by_fk')
                ->constrained('users', 'id')->restrictOnDelete();
            $table->foreignId('processed_by_user_id', 'apr_processed_by_fk')
                ->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->unsignedBigInteger('amount_rial');
            $table->text('reason');
            $table->string('status', 20)->default('processing');
            $table->string('gateway_ref_num', 120);
            $table->string('gateway_terminal_id', 80);
            $table->string('gateway_result_code', 40)->nullable();
            $table->text('gateway_result_message')->nullable();
            $table->longText('gateway_response')->nullable();
            $table->char('idempotency_key', 64)->unique('apr_idempotency_unique');
            $table->timestamp('requested_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at'], 'apr_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_payment_refunds');
    }
};
