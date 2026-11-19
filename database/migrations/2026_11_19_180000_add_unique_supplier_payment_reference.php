<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('clinic_supplier_payments')) {
            throw new RuntimeException('جدول پرداخت تأمین‌کنندگان برای ایجاد محدودیت یکتایی وجود ندارد.');
        }

        // پیش از افزودن قید، داده‌های قدیمی بررسی می‌شوند تا مهاجرت به‌صورت
        // ناخواسته رکورد مالی را حذف یا تغییر ندهد.
        $hasDuplicateReferences = DB::table('clinic_supplier_payments')
            ->select('doctor_id', 'reference')
            ->groupBy('doctor_id', 'reference')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateReferences) {
            throw new RuntimeException(
                'شماره پیگیری تکراری در پرداخت‌های تأمین‌کنندگان وجود دارد؛ ابتدا موارد تکراری را بررسی و اصلاح کنید.'
            );
        }

        Schema::table('clinic_supplier_payments', function (Blueprint $table): void {
            // یکتایی در سطح پایگاه داده، درخواست‌های هم‌زمان را نیز پوشش می‌دهد.
            $table->unique(
                ['doctor_id', 'reference'],
                'clinic_supplier_payments_doctor_reference_unique'
            );
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('clinic_supplier_payments')) {
            Schema::table('clinic_supplier_payments', function (Blueprint $table): void {
                $table->dropUnique('clinic_supplier_payments_doctor_reference_unique');
            });
        }
    }
};
