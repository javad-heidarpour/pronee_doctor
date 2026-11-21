<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('doctor_articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('slug', 200);
            $table->string('excerpt', 500)->nullable();
            $table->longText('body');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title', 180)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['doctor_id', 'slug'], 'doctor_articles_doctor_slug_unique');
            $table->index(['doctor_id', 'status', 'published_at'], 'doctor_articles_publish_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_articles');
    }
};
