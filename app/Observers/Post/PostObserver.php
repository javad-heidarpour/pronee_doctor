<?php
declare(strict_types=1);

namespace App\Observers\Post;

use App\Models\Post;
use App\Services\Post\SlugService;

class PostObserver
{
    public function creating(Post $post): void
    {
        // تنظیم نویسنده در صورت عدم وجود
        if (empty($post->author_id) && auth()->check()) {
            $post->author_id = auth()->id();
        }
    }

    public function updating(Post $post): void
    {
        // در صورت تغییر عنوان و عدم تغییر slug، slug جدید تولید کن
        if ($post->isDirty('title') && !$post->isDirty('slug')) {
            $post->slug = SlugService::generate($post->title);
        }
    }
}