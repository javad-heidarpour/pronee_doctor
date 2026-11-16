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

        // Preserve existing access while respecting the global maximum of
        // two clinics per secretary, even when a secretary is linked to
        // multiple doctors.
        $assignmentsBySecretary = DB::table('doctor_user')
            ->select('id', 'user_id', 'doctor_id')
            ->whereNull('clinic_ids')
            ->orderBy('user_id')
            ->orderBy('id')
            ->get()
            ->groupBy('user_id');

        foreach ($assignmentsBySecretary as $assignments) {
            $remainingClinicSlots = 2;

            foreach ($assignments as $assignment) {
                $clinicIds = [];

                if ($remainingClinicSlots > 0) {
                    $clinicIds = DB::table('addresses')
                        ->where('doctor_id', $assignment->doctor_id)
                        ->where('is_active', true)
                        ->whereNull('deleted_at')
                        ->orderBy('id')
                        ->limit($remainingClinicSlots)
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->all();
                }

                DB::table('doctor_user')
                    ->where('id', $assignment->id)
                    ->update([
                        'clinic_ids' => json_encode($clinicIds),
                    ]);

                $remainingClinicSlots -= count($clinicIds);
            }
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
