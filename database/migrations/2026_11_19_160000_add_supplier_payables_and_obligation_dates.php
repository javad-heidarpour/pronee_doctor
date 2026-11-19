<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clinic_staff_obligations', function (Blueprint $table): void {
            $table->date('planned_disbursement_date')->nullable()->after('first_due_date');
        });
        Schema::create('clinic_supplier_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('clinic_purchase_orders')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('clinic_suppliers')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('amount_rial');
            $table->string('method',24);
            $table->string('reference',120);
            $table->string('idempotency_key',160)->unique();
            $table->dateTime('paid_at');
            $table->timestamps();
            $table->index(['purchase_order_id','paid_at']);
            $table->index(['doctor_id','clinic_id','paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_supplier_payments');
        Schema::table('clinic_staff_obligations', function (Blueprint $table): void {
            $table->dropColumn('planned_disbursement_date');
        });
    }
};
