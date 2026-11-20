<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('time_slots', function (Blueprint $table): void {
            $table->string('cancelled_reason', 24)->nullable()->after('status');
        });

        // Existing cancelled slots were closed manually before reasons existed.
        DB::table('time_slots')
            ->where('status', 'cancelled')
            ->update(['cancelled_reason' => 'manual']);

        // Reclassify slots already closed by a registered clinic holiday.
        if (Schema::hasTable('doctor_holidays')) {
            DB::table('time_slots as slots')
                ->join('doctor_holidays as holidays', function ($join): void {
                    $join->on('slots.doctor_id', '=', 'holidays.doctor_id')
                        ->on('slots.clinic_id', '=', 'holidays.clinic_id')
                        ->on('slots.date', '=', 'holidays.holiday_date');
                })
                ->where('slots.status', 'cancelled')
                ->update(['slots.cancelled_reason' => 'holiday']);
        }
    }

    public function down(): void
    {
        Schema::table('time_slots', function (Blueprint $table): void {
            $table->dropColumn('cancelled_reason');
        });
    }
};
