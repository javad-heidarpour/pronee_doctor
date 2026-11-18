<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_user', function (Blueprint $table): void {
            $table->json('base_permissions')->nullable();
            $table->json('base_clinic_ids')->nullable();
            $table->boolean('managed_by_organization')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('doctor_user', function (Blueprint $table): void {
            $table->dropColumn(['base_permissions', 'base_clinic_ids', 'managed_by_organization']);
        });
    }
};
