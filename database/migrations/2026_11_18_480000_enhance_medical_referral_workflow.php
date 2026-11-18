<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_referrals', function (Blueprint $table): void {
            $table->foreignId('source_clinic_id')->nullable()->after('created_by_user_id')
                ->constrained('addresses')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable()->after('accepted_at');
            $table->text('result_summary')->nullable()->after('provider_response');
            $table->text('patient_instructions')->nullable()->after('result_summary');
            $table->index(['status', 'scheduled_at']);
            $table->index(['source_clinic_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('medical_referrals', function (Blueprint $table): void {
            $table->dropIndex(['status', 'scheduled_at']);
            $table->dropIndex(['source_clinic_id', 'created_at']);
            $table->dropConstrainedForeignId('source_clinic_id');
            $table->dropColumn(['scheduled_at', 'result_summary', 'patient_instructions']);
        });
    }
};
