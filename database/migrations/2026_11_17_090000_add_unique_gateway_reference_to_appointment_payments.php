<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('appointment_payments') ||
            ! Schema::hasColumn('appointment_payments', 'gateway_name') ||
            ! Schema::hasColumn('appointment_payments', 'ref_num')
        ) {
            throw new RuntimeException(
                'Cannot add SEP reference uniqueness: appointment_payments.gateway_name/ref_num is missing.'
            );
        }

        $hasDuplicates = DB::table('appointment_payments')
            ->select('gateway_name', 'ref_num')
            ->whereNotNull('ref_num')
            ->groupBy('gateway_name', 'ref_num')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            throw new RuntimeException(
                'Duplicate gateway reference numbers exist in appointment_payments. Resolve them before running this migration.'
            );
        }

        Schema::table('appointment_payments', function (Blueprint $table) {
            $table->unique(
                ['gateway_name', 'ref_num'],
                'appointment_payments_gateway_ref_unique'
            );
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('appointment_payments')) {
            Schema::table('appointment_payments', function (Blueprint $table) {
                $table->dropUnique('appointment_payments_gateway_ref_unique');
            });
        }
    }
};
