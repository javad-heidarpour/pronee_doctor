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
        Schema::create('medical_network_providers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('clinic_organizations')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('provider_type', 24);
            $table->string('name', 180);
            $table->string('slug', 210)->unique();
            $table->string('city', 100)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('phone', 45)->nullable();
            $table->string('email')->nullable();
            $table->string('license_number', 100)->nullable();
            $table->json('specialties')->nullable();
            $table->json('services')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 24)->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('completed_referrals_count')->default(0);
            $table->timestamps();
            $table->index(['provider_type', 'status', 'city']);
            $table->index(['organization_id', 'status']);
            $table->index(['created_by_user_id', 'status']);
        });

        Schema::create('medical_referrals', function (Blueprint $table): void {
            $table->id();
            $table->string('tracking_code', 20)->unique();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('referring_doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->foreignId('target_provider_id')->constrained('medical_network_providers')->restrictOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('service_type', 32);
            $table->string('priority', 16)->default('normal');
            $table->string('status', 24)->default('sent');
            $table->string('reason', 240);
            $table->text('clinical_summary')->nullable();
            $table->text('patient_message')->nullable();
            $table->timestamp('consent_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('provider_response')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['referring_doctor_id', 'status', 'created_at']);
            $table->index(['target_provider_id', 'status', 'created_at']);
            $table->index(['patient_id', 'created_at']);
        });

        Schema::create('medical_referral_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medical_referral_id')->constrained('medical_referrals')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 40);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24)->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['medical_referral_id', 'created_at']);
        });

        if (Schema::hasTable('menus')) {
            $menu = [
                'title' => 'شبکه همکاری پزشکی',
                'route_name' => 'portal.medical-network.index',
                'icon' => 'IconNetwork',
                'order' => 61,
                'guard' => 'doctor',
                'is_active' => true,
                'show_in_sidebar' => true,
                'permissions' => json_encode(['medical-network.view']),
                'roles' => json_encode(['doctor', 'secretary']),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (DB::table('menus')->where('route_name', $menu['route_name'])->exists()) {
                DB::table('menus')->where('route_name', $menu['route_name'])->update($menu);
            } else {
                DB::table('menus')->insert($menu);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('route_name', 'portal.medical-network.index')->delete();
        }
        Schema::dropIfExists('medical_referral_events');
        Schema::dropIfExists('medical_referrals');
        Schema::dropIfExists('medical_network_providers');
    }
};
