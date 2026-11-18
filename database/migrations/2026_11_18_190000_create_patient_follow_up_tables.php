<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_follow_up_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('category', 60)->default('general');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('default_days')->default(3);
            $table->string('contact_method', 20)->default('call');
            $table->boolean('send_sms')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['doctor_id', 'is_active']);
        });

        // A discoverable sidebar entry protected by the existing patient-view permission.
        if (Schema::hasTable('menus') && !DB::table('menus')->where('route_name', 'portal.follow-ups.index')->exists()) {
            DB::table('menus')->insert([
                'title' => 'پیگیری بیماران',
                'route_name' => 'portal.follow-ups.index',
                'icon' => 'IconClock',
                'order' => 55,
                'guard' => 'doctor',
                'is_active' => true,
                'show_in_sidebar' => true,
                'permissions' => json_encode(['patients.view']),
                'roles' => json_encode(['doctor', 'secretary']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Starter templates are created for doctors already registered in this installation.
        $now = now();
        foreach (DB::table('doctors')->pluck('id') as $doctorId) {
            foreach ([
                ['پیگیری پس از ویزیت', 'general', 'تماس برای بررسی وضعیت عمومی بیمار', 3, 'call'],
                ['یادآوری انجام آزمایش', 'lab', 'پیگیری انجام آزمایش و دریافت نتیجه', 7, 'call'],
                ['کنترل بیماری مزمن', 'chronic', 'پیگیری وضعیت و پایبندی به برنامه درمانی', 30, 'call'],
                ['کنترل اولیه پس از جراحی', 'post_op', 'تماس برای بررسی وضعیت پس از عمل و ارجاع موارد هشداردهنده به پزشک', 1, 'call'],
                ['پیگیری دوره‌ای پس از جراحی', 'surgery', 'بررسی روند بهبودی و نیاز به مراجعه مجدد', 7, 'review'],
                ['پیگیری خدمات زیبایی', 'cosmetic', 'بررسی رضایت و عوارض گزارش‌شده پس از خدمت', 3, 'call'],
            ] as [$title, $category, $description, $days, $method]) {
                DB::table('patient_follow_up_templates')->insert([
                    'doctor_id' => $doctorId,
                    'title' => $title,
                    'category' => $category,
                    'description' => $description,
                    'default_days' => $days,
                    'contact_method' => $method,
                    'send_sms' => false,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        Schema::create('patient_follow_up_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('patient_follow_up_templates')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('plan_uuid')->nullable();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->dateTime('due_at');
            $table->string('priority', 20)->default('normal');
            $table->string('contact_method', 20)->default('call');
            $table->boolean('send_sms')->default(false);
            $table->string('status', 24)->default('pending');
            $table->string('outcome', 40)->nullable();
            $table->text('result_notes')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('sms_sent_at')->nullable();
            $table->dateTime('sms_attempted_at')->nullable();
            $table->unsignedSmallInteger('sms_attempts')->default(0);
            $table->string('last_sms_error', 500)->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'status', 'due_at']);
            $table->index(['patient_id', 'due_at']);
            $table->index(['assigned_to', 'status', 'due_at']);
            $table->index(['sms_sent_at', 'sms_attempted_at', 'due_at'], 'follow_up_sms_dispatch_index');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('route_name', 'portal.follow-ups.index')->delete();
        }
        Schema::dropIfExists('patient_follow_up_tasks');
        Schema::dropIfExists('patient_follow_up_templates');
    }
};