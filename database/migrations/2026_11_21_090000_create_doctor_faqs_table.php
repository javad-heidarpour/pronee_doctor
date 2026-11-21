<?php

declare(strict_types=1);

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('doctor_faqs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->string('question', 240);
            $table->text('answer');
            $table->string('category', 80)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'is_published', 'sort_order'], 'doctor_faqs_public_order_idx');
        });

        if (Schema::hasTable('menus')) {
            DB::table('menus')->updateOrInsert(
                ['route_name' => 'portal.faqs.index'],
                [
                    'title' => 'پرسش‌های متداول',
                    'icon' => 'IconHelpCircle',
                    'order' => 73,
                    'guard' => 'doctor',
                    'is_active' => true,
                    'show_in_sidebar' => true,
                    'permissions' => json_encode([], JSON_UNESCAPED_UNICODE),
                    'roles' => json_encode(['doctor', 'admin'], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('route_name', 'portal.faqs.index')->delete();
        }
        Schema::dropIfExists('doctor_faqs');
    }
};
