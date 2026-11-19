<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_inventory_movements', function (Blueprint $table): void {
            // کلید یکتا از ثبت دوباره یک دریافت در اثر retry یا دوبار کلیک جلوگیری می‌کند.
            $table->uuid('idempotency_key')->nullable()->unique('clinic_inventory_movements_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_inventory_movements', function (Blueprint $table): void {
            $table->dropUnique('clinic_inventory_movements_idempotency_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};
