<?php
declare(strict_types=1);

namespace App\DTOs\Post;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class PostData
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $category_id,
        public readonly ?int $doctor_id,
        public readonly ?int $author_id,
        public readonly string $title,
        public readonly ?string $slug,
        public readonly ?string $excerpt,
        public readonly string $content,
        public readonly mixed $featured_image, // <-- تغییر به mixed
        public readonly ?string $meta_title,
        public readonly ?string $meta_description,
        public readonly ?string $canonical_url,
        public readonly array $keywords,
        public readonly array $tags,
        public readonly ?string $published_at,
        public readonly bool $is_featured,
        public readonly bool $is_pinned,
        public readonly bool $is_published,
    ) {}

    public static function fromRequest(Request $request, ?int $id = null): self
    {
        // اگر فایل جدید آپلود شده باشد، شیء فایل را می‌گیرد، در غیر این صورت مقدار متنی (آدرس قبلی) را
        $featuredImage = $request->hasFile('featured_image') 
            ? $request->file('featured_image') 
            : $request->input('featured_image');

        return new self(
            id: $id,
            category_id: $request->input('category_id'),
            doctor_id: $request->input('doctor_id'),
            author_id: $request->input('author_id') ?? auth()->id(),
            title: $request->input('title'),
            slug: $request->input('slug'),
            excerpt: $request->input('excerpt'),
            content: $request->input('content'),
            featured_image: $featuredImage, // <-- اینجا شیء فایل یا رشته قرار می‌گیرد
            meta_title: $request->input('meta_title'),
            meta_description: $request->input('meta_description'),
            canonical_url: $request->input('canonical_url'),
            keywords: $request->input('keywords', []),
            tags: $request->input('tags', []),
            published_at: $request->input('published_at'),
            is_featured: $request->boolean('is_featured'),
            is_pinned: $request->boolean('is_pinned'),
            is_published: $request->boolean('is_published'),
        );
    }

    public function toArray(): array
    {
        return [
            'category_id'      => $this->category_id,
            'doctor_id'        => $this->doctor_id,
            'author_id'        => $this->author_id,
            'title'            => $this->title,
            'slug'             => $this->slug,
            'excerpt'          => $this->excerpt,
            'content'          => $this->content,
            'meta_title'       => $this->meta_title,
            'meta_description' => $this->meta_description,
            'canonical_url'    => $this->canonical_url,
            'keywords'         => $this->keywords,
            'published_at'     => $this->published_at,
            'is_featured'      => $this->is_featured,
            'is_pinned'        => $this->is_pinned,
            'is_published'     => $this->is_published,
            
            // نکته حیاتی: اگر فایل باشد، اینجا null می‌گذاریم تا در سرویس هندل شود
            // اگر رشته باشد (عکس قبلی)، همان رشته رد می‌شود
            'featured_image'   => $this->featured_image instanceof UploadedFile ? null : $this->featured_image,
        ];
    }
}