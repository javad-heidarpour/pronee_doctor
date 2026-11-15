
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appointment_id')
                ->constrained('appointments')
                ->cascadeOnDelete();

            // مبلغ تراکنش به ریال
            $table->unsignedBigInteger('amount_rial');

            // pending, initiated, paid, failed, cancelled
            $table->string('status', 20)->default('pending');

            // شناسه درخواست داخلی ما
            $table->uuid('payment_uuid')->unique();

            // توکن برگشتی از درگاه
            $table->string('gateway_token')->nullable()->index();

            // شماره مرجع و شماره پیگیری درگاه
            $table->string('ref_num')->nullable()->index();
            $table->string('terminal_id')->nullable();

            $table->string('gateway_name', 50)
                ->default('saman');

            $table->text('gateway_result_message')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['appointment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_payments');
    }
};