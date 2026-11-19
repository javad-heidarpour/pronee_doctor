<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_professional_sessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('settlement_amount_rial')->default(0)->after('clinic_amount_rial');
            $table->timestamp('payer_confirmed_at')->nullable()->after('paid_at');
            $table->foreignId('payer_confirmed_by_user_id')->nullable()->after('payer_confirmed_at')->constrained('users')->nullOnDelete();
            $table->string('payer_reference', 120)->nullable()->after('payer_confirmed_by_user_id');
            $table->timestamp('receiver_confirmed_at')->nullable()->after('payer_reference');
            $table->foreignId('receiver_confirmed_by_user_id')->nullable()->after('receiver_confirmed_at')->constrained('users')->nullOnDelete();
            $table->string('receiver_reference', 120)->nullable()->after('receiver_confirmed_by_user_id');
            $table->string('status', 24)->default('pending')->change();
        });

        DB::table('clinic_professional_sessions')->orderBy('id')->chunkById(500, function ($sessions): void {
            foreach ($sessions as $session) {
                $direction = DB::table('clinic_professional_partnerships')
                    ->where('id', $session->partnership_id)->value('payment_direction');
                $amount = $direction === 'professional_to_clinic'
                    ? (int) $session->clinic_amount_rial
                    : (int) $session->professional_amount_rial;
                DB::table('clinic_professional_sessions')->where('id', $session->id)
                    ->update(['settlement_amount_rial' => $amount]);
            }
        });

        Schema::create('clinic_professional_dues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partnership_id')->constrained('clinic_professional_partnerships')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->restrictOnDelete();
            $table->foreignId('provider_id')->constrained('medical_network_providers')->restrictOnDelete();
            $table->foreignId('clinic_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('professional_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('due_date');
            $table->unsignedBigInteger('amount_rial');
            $table->string('payment_direction', 24);
            $table->string('status', 24)->default('pending');
            $table->timestamp('payer_confirmed_at')->nullable();
            $table->foreignId('payer_confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payer_reference', 120)->nullable();
            $table->timestamp('receiver_confirmed_at')->nullable();
            $table->foreignId('receiver_confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receiver_reference', 120)->nullable();
            $table->timestamps();
            $table->unique(['partnership_id', 'period_start', 'period_end'], 'cpd_partnership_period_unique');
            $table->index(['doctor_id', 'clinic_id', 'status'], 'cpd_clinic_status');
            $table->index(['professional_user_id', 'status'], 'cpd_professional_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_professional_dues');
        DB::table('clinic_professional_sessions')->where('status', 'partially_confirmed')->update(['status' => 'pending']);
        Schema::table('clinic_professional_sessions', function (Blueprint $table): void {
            $table->dropForeign(['payer_confirmed_by_user_id']);
            $table->dropForeign(['receiver_confirmed_by_user_id']);
            $table->dropColumn([
                'settlement_amount_rial', 'payer_confirmed_at', 'payer_confirmed_by_user_id', 'payer_reference',
                'receiver_confirmed_at', 'receiver_confirmed_by_user_id', 'receiver_reference',
            ]);
            $table->string('status', 16)->default('pending')->change();
        });
    }
};
