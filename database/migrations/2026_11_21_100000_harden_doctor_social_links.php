<?php

declare(strict_types=1);

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('doctor_socials') || !Schema::hasColumn('doctor_socials', 'social_platform_id')) {
            return;
        }

        DB::statement('DELETE duplicate_row FROM doctor_socials AS duplicate_row INNER JOIN doctor_socials AS kept_row ON duplicate_row.doctor_id = kept_row.doctor_id AND duplicate_row.social_platform_id = kept_row.social_platform_id AND duplicate_row.id > kept_row.id');

        Schema::table('doctor_socials', function ($table): void {
            $table->unique(['doctor_id', 'social_platform_id'], 'doctor_socials_doctor_platform_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('doctor_socials')) {
            Schema::table('doctor_socials', function ($table): void {
                $table->dropUnique('doctor_socials_doctor_platform_unique');
            });
        }
    }
};
