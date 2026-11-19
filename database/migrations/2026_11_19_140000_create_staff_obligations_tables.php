<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinic_staff_obligations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->restrictOnDelete();
            $table->foreignId('staff_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 16); // advance | loan
            $table->string('title', 180);
            $table->unsignedBigInteger('principal_amount');
            $table->unsignedBigInteger('installment_amount')->default(0);
            $table->unsignedSmallInteger('installment_count')->default(1);
            $table->unsignedBigInteger('repaid_amount')->default(0);
            $table->unsignedBigInteger('outstanding_amount');
            $table->date('first_due_date');
            $table->string('status', 20)->default('draft'); // draft, approved, active, settled, rejected
            $table->dateTime('disbursed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['organization_id','clinic_id','staff_user_id','status'], 'staff_obligation_scope_idx');
        });

        Schema::create('clinic_staff_obligation_installments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('obligation_id')->constrained('clinic_staff_obligations')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->date('due_date');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->string('status', 16)->default('pending');
            $table->timestamps();
            $table->unique(['obligation_id','sequence'], 'staff_obligation_installment_sequence_unique');
            $table->index(['due_date','status'], 'staff_obligation_due_idx');
        });

        Schema::create('clinic_staff_obligation_repayments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('obligation_id')->constrained('clinic_staff_obligations')->restrictOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('method', 24);
            $table->string('reference', 120)->nullable();
            $table->string('idempotency_key', 120)->unique();
            $table->dateTime('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['obligation_id','paid_at'], 'staff_obligation_repayment_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_staff_obligation_repayments');
        Schema::dropIfExists('clinic_staff_obligation_installments');
        Schema::dropIfExists('clinic_staff_obligations');
    }
};
