<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_treatment_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->string('specialty_module', 40)->default('general');
            $table->string('status', 24)->default('proposed');
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('deposit_amount')->default(0);
            $table->unsignedSmallInteger('installment_count')->default(1);
            $table->text('clinical_notes')->nullable();
            $table->text('financial_terms')->nullable();
            $table->boolean('sms_reminders_enabled')->default(false);
            $table->boolean('sms_consent_confirmed')->default(false);
            $table->date('started_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'clinic_id', 'status']);
            $table->index(['patient_id', 'status']);
        });

        Schema::create('patient_treatment_installments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained('patient_treatment_plans')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->date('due_date');
            $table->string('status', 20)->default('pending');
            $table->dateTime('last_reminded_at')->nullable();
            $table->timestamps();
            $table->unique(['treatment_plan_id', 'sequence']);
            $table->index(['status', 'due_date']);
        });

        Schema::create('patient_installment_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('installment_id')->constrained('patient_treatment_installments')->restrictOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('method', 24)->default('cash');
            $table->string('reference', 120)->nullable();
            $table->dateTime('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['paid_at', 'method']);
        });

        Schema::create('patient_collaterals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained('patient_treatment_plans')->cascadeOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 24);
            $table->string('identifier', 120)->nullable();
            $table->unsignedBigInteger('face_value')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 24)->default('held');
            $table->dateTime('received_at')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['treatment_plan_id', 'status']);
        });

        Schema::create('clinic_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 60);
            $table->string('description', 180);
            $table->unsignedBigInteger('amount');
            $table->date('expense_date');
            $table->string('payment_method', 24)->default('cash');
            $table->string('reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'clinic_id', 'expense_date']);
        });

        Schema::create('clinic_financial_ledger', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->restrictOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained('patient_treatment_plans')->nullOnDelete();
            $table->foreignId('installment_payment_id')->nullable()->constrained('patient_installment_payments')->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained('clinic_expenses')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entry_type', 24);
            $table->string('description', 180);
            $table->unsignedBigInteger('debit')->default(0);
            $table->unsignedBigInteger('credit')->default(0);
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->index(['doctor_id', 'clinic_id', 'occurred_at']);
            $table->index(['patient_id', 'occurred_at']);
        });

        if (Schema::hasTable('menus') && !DB::table('menus')->where('route_name', 'portal.finance.index')->exists()) {
            DB::table('menus')->insert([
                'title' => 'مدیریت مالی و اقساط',
                'route_name' => 'portal.finance.index',
                'icon' => 'IconWallet',
                'order' => 58,
                'guard' => 'doctor',
                'is_active' => true,
                'show_in_sidebar' => true,
                'permissions' => json_encode(['finance.view']),
                'roles' => json_encode(['doctor', 'secretary']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('route_name', 'portal.finance.index')->delete();
        }
        Schema::dropIfExists('clinic_financial_ledger');
        Schema::dropIfExists('clinic_expenses');
        Schema::dropIfExists('patient_collaterals');
        Schema::dropIfExists('patient_installment_payments');
        Schema::dropIfExists('patient_treatment_installments');
        Schema::dropIfExists('patient_treatment_plans');
    }
};