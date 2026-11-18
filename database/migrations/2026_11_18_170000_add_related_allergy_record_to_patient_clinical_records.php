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
            $table->foreignId('related_allergy_record_id')
                ->nullable()
                ->after('related_record_id')
                ->constrained('patient_clinical_records')
                ->nullOnDelete();
            $table->index(['patient_id', 'related_allergy_record_id'], 'patient_clinical_allergy_link_index');
        });
    }

    public function down(): void
    {
        Schema::table('patient_clinical_records', function (Blueprint $table): void {
            $table->dropIndex('patient_clinical_allergy_link_index');
            $table->dropConstrainedForeignId('related_allergy_record_id');
        });
    }
};
