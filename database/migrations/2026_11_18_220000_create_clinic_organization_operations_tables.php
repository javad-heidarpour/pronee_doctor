<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_organizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $table->string('name', 180);
            $table->string('slug', 200)->unique();
            $table->string('legal_name', 200)->nullable();
            $table->string('registration_number', 80)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('timezone', 64)->default('Asia/Tehran');
            $table->string('status', 24)->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->index(['owner_user_id', 'status']);
        });

        Schema::table('addresses', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->after('doctor_id')
                ->constrained('clinic_organizations')->nullOnDelete();
            $table->index(['organization_id', 'is_active']);
        });

        Schema::create('clinic_organization_doctors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->string('role', 32)->default('practitioner');
            $table->unsignedSmallInteger('revenue_share_basis_points')->default(7000);
            $table->string('status', 20)->default('active');
            $table->date('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'doctor_id']);
            $table->index(['doctor_id', 'status']);
        });

        Schema::create('clinic_organization_clinics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->cascadeOnDelete();
            $table->string('branch_code', 40)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'clinic_id']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('clinic_organization_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 32)->default('staff');
            $table->json('permissions')->nullable();
            $table->json('clinic_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'user_id']);
            $table->index(['user_id', 'is_active']);
        });

        Schema::create('clinic_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->string('code', 60)->nullable();
            $table->string('name', 180);
            $table->string('specialty_module', 40)->default('general');
            $table->unsignedSmallInteger('default_duration_minutes')->default(30);
            $table->unsignedBigInteger('default_price')->default(0);
            $table->unsignedSmallInteger('doctor_share_basis_points')->default(7000);
            $table->boolean('requires_follow_up')->default(false);
            $table->unsignedSmallInteger('follow_up_after_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['organization_id', 'specialty_module', 'is_active']);
            $table->index(['clinic_id', 'is_active']);
        });

        Schema::create('patient_treatment_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained('patient_treatment_plans')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('clinic_services')->nullOnDelete();
            $table->foreignId('provider_doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->string('item_type', 32)->default('procedure');
            $table->string('title', 180);
            $table->string('tooth_number', 8)->nullable();
            $table->string('tooth_surface', 24)->nullable();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->string('stage', 32)->default('planned');
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('recognized_amount')->default(0);
            $table->unsignedBigInteger('paid_applied_amount')->default(0);
            $table->unsignedSmallInteger('doctor_share_basis_points')->nullable();
            $table->unsignedSmallInteger('follow_up_after_days')->nullable();
            $table->dateTime('performed_at')->nullable();
            $table->dateTime('follow_up_due_at')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['treatment_plan_id', 'stage', 'sequence'], 'treatment_items_plan_stage_idx');
            $table->index(['provider_doctor_id', 'performed_at']);
        });

        Schema::create('patient_dental_chart_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->string('dentition', 12)->default('permanent');
            $table->string('tooth_number', 8);
            $table->string('surface', 24)->nullable();
            $table->string('condition', 40);
            $table->string('severity', 20)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('recorded_at');
            $table->timestamps();
            $table->index(['patient_id', 'tooth_number', 'recorded_at'], 'dental_chart_patient_tooth_idx');
        });

        Schema::create('clinic_financial_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->string('code', 32);
            $table->string('scope_key', 160)->unique();
            $table->string('name', 120);
            $table->string('type', 20);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'clinic_id', 'code'], 'finance_account_scope_code_unique');
        });

        Schema::create('clinic_journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('clinic_organizations')->nullOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_type', 60)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('description', 240);
            $table->dateTime('occurred_at');
            $table->string('status', 20)->default('posted');
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->timestamps();
            $table->index(['organization_id', 'occurred_at', 'status']);
            $table->index(['clinic_id', 'occurred_at']);
        });

        Schema::create('clinic_journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('clinic_journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('clinic_financial_accounts')->restrictOnDelete();
            $table->unsignedBigInteger('debit')->default(0);
            $table->unsignedBigInteger('credit')->default(0);
            $table->string('memo', 180)->nullable();
            $table->timestamps();
            $table->index(['account_id', 'journal_entry_id']);
        });

        Schema::create('doctor_settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('gross_amount');
            $table->unsignedBigInteger('clinic_share_amount')->default(0);
            $table->unsignedBigInteger('adjustment_amount')->default(0);
            $table->unsignedBigInteger('net_amount');
            $table->string('status', 20)->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'doctor_id', 'period_start', 'period_end'], 'doctor_settlement_period_unique');
        });

        Schema::create('clinic_staff_payrolls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->foreignId('staff_user_id')->constrained('users')->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('gross_amount');
            $table->unsignedBigInteger('deduction_amount')->default(0);
            $table->unsignedBigInteger('net_amount');
            $table->string('status', 20)->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'period_start', 'status']);
            $table->index(['staff_user_id', 'period_start']);
        });

        Schema::create('clinic_inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->cascadeOnDelete();
            $table->string('sku', 80)->nullable();
            $table->string('name', 180);
            $table->string('unit', 24)->default('عدد');
            $table->unsignedDecimal('minimum_quantity', 12, 3)->default(0);
            $table->unsignedDecimal('current_quantity', 12, 3)->default(0);
            $table->unsignedBigInteger('average_unit_cost')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['clinic_id', 'sku']);
            $table->index(['clinic_id', 'current_quantity']);
        });

        Schema::create('clinic_inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('clinic_inventory_items')->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('movement_type', 20);
            $table->decimal('quantity', 12, 3);
            $table->unsignedBigInteger('unit_cost')->default(0);
            $table->string('reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->index(['inventory_item_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_inventory_movements');
        Schema::dropIfExists('clinic_inventory_items');
        Schema::dropIfExists('clinic_staff_payrolls');
        Schema::dropIfExists('doctor_settlements');
        Schema::dropIfExists('clinic_journal_lines');
        Schema::dropIfExists('clinic_journal_entries');
        Schema::dropIfExists('clinic_financial_accounts');
        Schema::dropIfExists('patient_dental_chart_entries');
        Schema::dropIfExists('patient_treatment_items');
        Schema::dropIfExists('clinic_services');
        Schema::dropIfExists('clinic_organization_members');
        Schema::dropIfExists('clinic_organization_clinics');
        Schema::dropIfExists('clinic_organization_doctors');
        Schema::table('addresses', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'is_active']);
            $table->dropConstrainedForeignId('organization_id');
        });
        Schema::dropIfExists('clinic_organizations');
    }
};
