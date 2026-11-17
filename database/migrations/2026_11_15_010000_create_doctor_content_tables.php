<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fees', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->default('تعرفه ویزیت');
            $table->unsignedBigInteger('fee_rial');
            $table->boolean('is_active')->default(true)->index('fees_active_index');
            $table->unsignedBigInteger('created_by')->nullable()->index('fees_created_by_index');
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->string('phone');
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('address');
            $table->string('city')->default('تهران');
            $table->string('country')->default('IR');
            $table->json('working_hours')->nullable();
            $table->json('social_links')->nullable();
            $table->timestamps();
        });

        Schema::create('doctor_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->string('image_url');
            $table->string('caption')->nullable();
            $table->string('category')->default('clinic');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(
                ['doctor_id', 'category', 'is_active'],
                'doctor_images_doctor_id_category_is_active_index'
            );
        });

        Schema::create('doctor_socials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('social_platform_id')->constrained('social_platforms')->cascadeOnDelete();
            $table->string('username')->nullable();
            $table->string('url')->nullable();
            $table->boolean('is_visible')->nullable()->default(true);
            $table->timestamps();
        });

        Schema::create('honor_badges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->string('title');
            $table->string('icon')->default('✨');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('medical_services', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('category', ['general_surgery', 'cosmetic_surgery', 'obesity_surgery']);
            $table->text('icon_html')->nullable();
            $table->string('cover_image', 500)->nullable();
            $table->string('thumbnail_image', 500)->nullable();
            $table->string('short_description', 500)->nullable();
            $table->string('intro_title')->nullable();
            $table->longText('intro_html')->nullable();
            $table->string('content_heading')->nullable();
            $table->longText('content_html')->nullable();
            $table->string('benefits_heading')->nullable();
            $table->longText('benefits_html')->nullable();
            $table->string('preparation_heading')->nullable();
            $table->longText('preparation_html')->nullable();
            $table->string('recovery_heading')->nullable();
            $table->longText('recovery_html')->nullable();
            $table->json('faq_json')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->string('og_image', 500)->nullable();
            $table->json('schema_json')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index('category', 'medical_services_category_index');
            $table->index('is_active', 'medical_services_active_index');
            $table->index('is_featured', 'medical_services_featured_index');
            $table->index('sort_order', 'medical_services_sort_index');
        });

        Schema::create('surgical_procedures', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('short_description', 500)->nullable();
            $table->string('category', 100)->nullable();
            $table->text('icon_html')->nullable();
            $table->string('cover_image', 500)->nullable();
            $table->string('thumbnail_image', 500)->nullable();
            $table->string('intro_title')->nullable();
            $table->longText('intro_html')->nullable();
            $table->string('indications_heading')->nullable();
            $table->longText('indications_html')->nullable();
            $table->string('procedure_heading')->nullable();
            $table->longText('procedure_html')->nullable();
            $table->string('benefits_heading')->nullable();
            $table->longText('benefits_html')->nullable();
            $table->string('preparation_heading')->nullable();
            $table->longText('preparation_html')->nullable();
            $table->string('recovery_heading')->nullable();
            $table->longText('recovery_html')->nullable();
            $table->string('risks_heading')->nullable();
            $table->longText('risks_html')->nullable();
            $table->json('faq_json')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->string('og_image', 500)->nullable();
            $table->json('schema_json')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index('category', 'surgical_procedures_category_index');
            $table->index('is_active', 'surgical_procedures_active_index');
            $table->index('is_featured', 'surgical_procedures_featured_index');
            $table->index('sort_order', 'surgical_procedures_sort_index');
        });

        Schema::create('menus', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('route_name')->nullable();
            $table->string('url')->nullable();
            $table->string('icon')->nullable();
            $table->integer('order')->default(0);
            $table->string('guard')->default('doctor');
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_sidebar')->default(true);
            $table->json('permissions')->nullable();
            $table->json('roles')->nullable();
            $table->timestamps();
            $table->index(['guard', 'is_active', 'order'], 'menus_guard_is_active_order_index');
        });

        Schema::create('menus_home1', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('route_name')->nullable();
            $table->string('url')->nullable();
            $table->string('icon')->nullable();
            $table->integer('order')->default(0);
            $table->string('guard')->default('doctor');
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_sidebar')->default(true);
            $table->json('permissions')->nullable();
            $table->timestamps();
            $table->index(['guard', 'is_active', 'order'], 'menus_home1_guard_is_active_order_index');
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->string('name');
            $table->text('comment');
            $table->string('service')->nullable();
            $table->tinyInteger('rating')->default(5);
            $table->boolean('is_approved')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('reviews')->cascadeOnDelete();
            $table->string('patient_name')->nullable();
            $table->tinyInteger('rating');
            $table->text('comment');
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_anonymous')->default(false);
            $table->text('doctor_reply')->nullable();
            $table->timestamp('doctor_reply_at')->nullable();
            $table->timestamps();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['doctor_id', 'is_approved'], 'reviews_doctor_id_is_approved_index');
            $table->index('rating', 'reviews_rating_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('menus_home1');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('surgical_procedures');
        Schema::dropIfExists('medical_services');
        Schema::dropIfExists('honor_badges');
        Schema::dropIfExists('doctor_socials');
        Schema::dropIfExists('doctor_images');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('fees');
    }
};
