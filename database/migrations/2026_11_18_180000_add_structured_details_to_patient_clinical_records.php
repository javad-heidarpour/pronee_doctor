<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_clinical_records', function (Blueprint $table): void {
            $table->string('reaction_severity', 24)->nullable()->after('related_allergy_record_id');
            $table->date('reaction_date')->nullable()->after('reaction_severity');
            $table->text('action_taken')->nullable()->after('reaction_date');
            $table->string('reaction_outcome', 24)->nullable()->after('action_taken');
        });
    }

    public function down(): void
    {
        Schema::table('patient_clinical_records', function (Blueprint $table): void {
            $table->dropColumn([
                'reaction_severity',
                'reaction_date',
                'action_taken',
                'reaction_outcome',
            ]);
        });
    }
};
