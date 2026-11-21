<?php

declare(strict_types=1);

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_satisfaction_surveys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->unsignedTinyInteger('overall_rating');
            $table->unsignedTinyInteger('communication_rating');
            $table->unsignedTinyInteger('waiting_rating');
            $table->unsignedTinyInteger('facility_rating');
            $table->text('comment')->nullable();
            $table->boolean('is_anonymous')->default(true);
            $table->timestamps();
            $table->index(['doctor_id', 'created_at'], 'satisfaction_doctor_created_idx');
        });

        if (Schema::hasTable('menus')) {
            DB::table('menus')->updateOrInsert(
                ['route_name' => 'portal.satisfaction.index'],
                [
                    'title' => 'رضایت بیماران',
                    'icon' => 'IconStar',
                    'order' => 75,
                    'guard' => 'doctor',
                    'is_active' => true,
                    'show_in_sidebar' => true,
                    'permissions' => json_encode(['appointments.view'], JSON_UNESCAPED_UNICODE),
                    'roles' => json_encode(['doctor', 'admin', 'secretary'], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('route_name', 'portal.satisfaction.index')->delete();
        }
        Schema::dropIfExists('appointment_satisfaction_surveys');
    }
};
