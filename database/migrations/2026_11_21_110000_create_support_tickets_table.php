<?php

declare(strict_types=1);

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference', 20)->unique();
            $table->string('name', 120);
            $table->string('email', 190);
            $table->string('category', 30)->default('other');
            $table->string('subject', 180);
            $table->text('message');
            $table->string('status', 20)->default('open')->index();
            $table->string('priority', 20)->default('normal');
            $table->text('staff_response')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at'], 'support_tickets_status_created_idx');
            $table->index(['email', 'created_at'], 'support_tickets_email_created_idx');
        });

        if (Schema::hasTable('menus')) {
            DB::table('menus')->updateOrInsert(
                ['route_name' => 'portal.support-tickets.index'],
                [
                    'title' => 'درخواست‌های پشتیبانی',
                    'icon' => 'IconLifeBuoy',
                    'order' => 74,
                    'guard' => 'doctor',
                    'is_active' => true,
                    'show_in_sidebar' => true,
                    'permissions' => json_encode([], JSON_UNESCAPED_UNICODE),
                    'roles' => json_encode(['admin'], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('route_name', 'portal.support-tickets.index')->delete();
        }
        Schema::dropIfExists('support_tickets');
    }
};
