<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinic_staff_contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('staff_user_id')->constrained('users')->restrictOnDelete();
            $table->string('compensation_type', 16)->default('monthly');
            $table->unsignedBigInteger('base_monthly_salary')->default(0);
            $table->unsignedBigInteger('hourly_rate')->default(0);
            $table->unsignedInteger('standard_monthly_minutes')->default(9600);
            $table->unsignedSmallInteger('overtime_multiplier_basis_points')->default(15000);
            $table->decimal('annual_leave_days', 5, 2)->unsigned()->default(26);
            $table->json('paid_leave_types')->nullable();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['organization_id','staff_user_id','is_active'], 'staff_contract_scope_idx');
        });

        Schema::create('clinic_staff_leave_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('staff_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('entitled_days', 6, 2)->default(0);
            $table->decimal('carry_over_days', 6, 2)->default(0);
            $table->decimal('adjustment_days', 6, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id','staff_user_id','year'], 'staff_leave_balance_year_unique');
        });

        Schema::table('clinic_staff_payrolls', function (Blueprint $table): void {
            $table->dropUnique('clinic_staff_payroll_period_unique');
            $table->unique(['organization_id','staff_user_id','period_start','period_end','clinic_id'], 'clinic_staff_payroll_clinic_period_unique');
            $table->unsignedInteger('worked_minutes')->default(0)->after('net_amount');
            $table->unsignedInteger('scheduled_minutes')->default(0)->after('worked_minutes');
            $table->unsignedInteger('paid_leave_minutes')->default(0)->after('scheduled_minutes');
            $table->unsignedInteger('overtime_minutes')->default(0)->after('paid_leave_minutes');
            $table->unsignedBigInteger('base_pay')->default(0)->after('overtime_minutes');
            $table->unsignedBigInteger('overtime_pay')->default(0)->after('base_pay');
            $table->unsignedBigInteger('allowance_amount')->default(0)->after('overtime_pay');
            $table->json('calculation_snapshot')->nullable()->after('allowance_amount');
        });

        Schema::create('clinic_staff_shift_replacements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('absent_staff_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('replacement_staff_user_id')->constrained('users', 'id', 'cssr_replacement_staff_user_fk')->restrictOnDelete();
            $table->date('work_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 20)->default('assigned');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id','clinic_id','absent_staff_user_id','work_date'], 'staff_shift_replacement_unique');
            $table->index(['organization_id','replacement_staff_user_id','work_date'], 'staff_replacement_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_staff_shift_replacements');
        Schema::table('clinic_staff_payrolls', function (Blueprint $table): void {
            $table->dropUnique('clinic_staff_payroll_clinic_period_unique');
            $table->unique(['organization_id','staff_user_id','period_start','period_end'], 'clinic_staff_payroll_period_unique');
            $table->dropColumn(['worked_minutes','scheduled_minutes','paid_leave_minutes','overtime_minutes','base_pay','overtime_pay','allowance_amount','calculation_snapshot']);
        });
        Schema::dropIfExists('clinic_staff_leave_balances');
        Schema::dropIfExists('clinic_staff_contracts');
    }
};
