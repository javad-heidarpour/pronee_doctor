<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('doctor_holidays', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->cascadeOnDelete();
            $table->date('holiday_date');
            $table->string('title', 160);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['doctor_id', 'clinic_id', 'holiday_date'],
                'doctor_clinic_holiday_unique'
            );
            $table->index(['doctor_id', 'holiday_date'], 'doctor_holiday_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_holidays');
    }
};
