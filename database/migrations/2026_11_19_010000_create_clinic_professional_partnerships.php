<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_professional_partnerships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->restrictOnDelete();
            $table->foreignId('provider_id')->constrained('medical_network_providers')->restrictOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('model', 24);
            $table->string('service_name', 160);
            $table->unsignedBigInteger('service_fee_rial')->default(0);
            $table->unsignedInteger('professional_share_bps')->default(7000);
            $table->unsignedInteger('clinic_share_bps')->default(3000);
            $table->unsignedBigInteger('fixed_fee_rial')->default(0);
            $table->unsignedBigInteger('periodic_fee_rial')->default(0);
            $table->string('periodic_cycle', 16)->nullable();
            $table->string('payment_direction', 24)->default('clinic_to_professional');
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->string('status', 20)->default('proposed');
            $table->timestamp('accepted_at')->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'clinic_id', 'status'], 'cpp_doctor_clinic_status');
            $table->index(['provider_id', 'status'], 'cpp_provider_status');
        });

        Schema::create('clinic_professional_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partnership_id')->constrained('clinic_professional_partnerships')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->restrictOnDelete();
            $table->foreignId('provider_id')->constrained('medical_network_providers')->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->unique()->constrained('appointments')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('service_name', 160);
            $table->date('service_date');
            $table->unsignedBigInteger('gross_amount_rial');
            $table->unsignedBigInteger('adjustment_amount_rial')->default(0);
            $table->unsignedBigInteger('professional_amount_rial');
            $table->unsignedBigInteger('clinic_amount_rial');
            $table->string('status', 16)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_reference', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'clinic_id', 'service_date'], 'cps_doctor_clinic_date');
            $table->index(['partnership_id', 'status'], 'cps_partnership_status');
        });

        if (Schema::hasTable('menus')) {
            DB::table('menus')->updateOrInsert(
                ['route_name' => 'portal.professional-partnerships.index'],
                [
                    'title' => 'همکاری تخصصی و درآمد مشترک',
                    'icon' => 'IconHandshake',
                    'order' => 62,
                    'guard' => 'doctor',
                    'is_active' => true,
                    'show_in_sidebar' => true,
                    'permissions' => json_encode(['finance.view']),
                    'roles' => json_encode(['doctor', 'secretary']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('route_name', 'portal.professional-partnerships.index')->delete();
        }
        Schema::dropIfExists('clinic_professional_sessions');
        Schema::dropIfExists('clinic_professional_partnerships');
    }
};
