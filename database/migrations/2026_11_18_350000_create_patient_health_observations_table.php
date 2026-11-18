<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_health_observations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('measurement_type', 32);
            $table->decimal('value', 10, 2);
            $table->string('unit', 16);
            $table->timestamp('observed_at');
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'measurement_type', 'observed_at'], 'patient_health_obs_patient_type_date_idx');
            $table->index(['user_id', 'observed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_health_observations');
    }
};
