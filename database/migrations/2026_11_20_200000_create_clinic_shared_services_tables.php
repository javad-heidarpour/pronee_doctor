<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinic_shared_services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id', 'css_org_fk')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('doctor_id', 'css_doctor_fk')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id', 'css_clinic_fk')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('created_by_user_id', 'css_creator_fk')->constrained('users')->restrictOnDelete();
            $table->string('title', 160);
            $table->string('category', 60);
            $table->text('description');
            $table->unsignedBigInteger('price_rial')->default(0);
            $table->unsignedSmallInteger('weekly_capacity')->default(1);
            $table->string('status', 16)->default('published');
            $table->date('available_from')->nullable();
            $table->date('available_until')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status', 'category'], 'css_org_status_category_idx');
        });

        Schema::create('clinic_shared_service_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shared_service_id', 'cssr_service_fk')->constrained('clinic_shared_services')->restrictOnDelete();
            $table->foreignId('requesting_organization_id', 'cssr_request_org_fk')->constrained('clinic_organizations')->cascadeOnDelete();
            $table->foreignId('requesting_doctor_id', 'cssr_request_doctor_fk')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('requesting_clinic_id', 'cssr_request_clinic_fk')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id', 'cssr_requested_by_fk')->constrained('users')->restrictOnDelete();
            $table->foreignId('responded_by_user_id', 'cssr_responded_by_fk')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('requested_capacity')->default(1);
            $table->string('status', 16)->default('pending');
            $table->text('request_note')->nullable();
            $table->text('response_note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['requesting_organization_id', 'status'], 'cssr_request_org_status_idx');
        });

        if (Schema::hasTable('menus')) {
            DB::table('menus')->updateOrInsert(['route_name' => 'portal.shared-services.index'], [
                'title' => 'خدمات مشترک مراکز',
                'icon' => 'IconNetwork',
                'order' => 67,
                'guard' => 'doctor',
                'is_active' => true,
                'show_in_sidebar' => true,
                'permissions' => json_encode(['appointments.view','appointments.manage']),
                'roles' => json_encode(['doctor']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('route_name', 'portal.shared-services.index')->delete();
        }
        Schema::dropIfExists('clinic_shared_service_requests');
        Schema::dropIfExists('clinic_shared_services');
    }
};
