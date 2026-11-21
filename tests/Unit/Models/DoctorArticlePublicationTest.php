<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Doctor;
use App\Models\DoctorArticle;
use App\Http\Controllers\Portal\DoctorArticleController;
use ReflectionMethod;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class DoctorArticlePublicationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('family')->nullable();
            $table->string('caption')->nullable();
            $table->string('slug')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('doctor_articles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('title');
            $table->string('slug');
            $table->string('excerpt')->nullable();
            $table->longText('body');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('doctor_articles');
        Schema::dropIfExists('doctors');

        parent::tearDown();
    }

    public function test_published_scope_excludes_drafts_future_and_unpublished_articles(): void
    {
        $doctor = Doctor::query()->create(['name' => 'Doctor', 'is_active' => true]);

        $published = DoctorArticle::query()->create([
            'doctor_id' => $doctor->id,
            'title' => 'Published article',
            'slug' => 'published-article',
            'body' => 'Published content for patient education.',
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ]);

        DoctorArticle::query()->create([
            'doctor_id' => $doctor->id,
            'title' => 'Draft article',
            'slug' => 'draft-article',
            'body' => 'Draft content for patient education.',
            'status' => 'draft',
            'published_at' => now(),
        ]);

        DoctorArticle::query()->create([
            'doctor_id' => $doctor->id,
            'title' => 'Future article',
            'slug' => 'future-article',
            'body' => 'Future content for patient education.',
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $this->assertSame(
            [$published->id],
            DoctorArticle::query()->published()->pluck('id')->all()
        );
    }

    public function test_soft_deleted_article_is_not_publicly_publishable(): void
    {
        $doctor = Doctor::query()->create(['name' => 'Doctor', 'is_active' => true]);
        $article = DoctorArticle::query()->create([
            'doctor_id' => $doctor->id,
            'title' => 'Removed article',
            'slug' => 'removed-article',
            'body' => 'This content has been removed from publication.',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        $article->delete();

        $this->assertSame(0, DoctorArticle::query()->published()->count());
        $this->assertSame(1, DoctorArticle::withTrashed()->count());
    }
    public function test_slug_generation_does_not_reuse_a_soft_deleted_article_slug(): void
    {
        $doctor = Doctor::query()->create(['name' => 'Doctor', 'is_active' => true]);
        DoctorArticle::query()->create([
            'doctor_id' => $doctor->id,
            'title' => 'Same article title',
            'slug' => 'same-article-title',
            'body' => 'Previously published content for patient education.',
        ])->delete();

        $slugMethod = new ReflectionMethod(DoctorArticleController::class, 'uniqueSlug');
        $slugMethod->setAccessible(true);
        $slug = $slugMethod->invoke(new DoctorArticleController(), $doctor->id, 'Same article title');

        $this->assertSame('same-article-title-2', $slug);
    }

}
