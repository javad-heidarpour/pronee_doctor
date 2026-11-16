<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('doctor_user', 'clinic_ids')) {
            Schema::table('doctor_user', function (Blueprint $table) {
                $table->json('clinic_ids')->nullable()->after('permissions');
            });
        }

        // Preserve existing secretary access by assigning up to two active
        // addresses belonging to each already-linked doctor.
        $assignments = DB::table('doctor_user')
            ->select('id', 'doctor_id')
            ->whereNull('clinic_ids')
            ->get();

        foreach ($assignments as $assignment) {
            $clinicIds = DB::table('addresses')
                ->where('doctor_id', $assignment->doctor_id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->limit(2)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            DB::table('doctor_user')
                ->where('id', $assignment->id)
                ->update([
                    'clinic_ids' => json_encode($clinicIds),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('doctor_user', 'clinic_ids')) {
            Schema::table('doctor_user', function (Blueprint $table) {
                $table->dropColumn('clinic_ids');
            });
        }
    }
};
