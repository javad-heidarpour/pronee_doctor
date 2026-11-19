<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_installment_payments', function (Blueprint $table): void {
            // برای سوابق قدیمی مقدار null حفظ می‌شود؛ پرداخت‌های جدید با UUID یکتا ثبت خواهند شد.
            $table->uuid('idempotency_key')->nullable()->unique('patient_installment_payments_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('patient_installment_payments', function (Blueprint $table): void {
            $table->dropUnique('patient_installment_payments_idempotency_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};
