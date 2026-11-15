<?php

declare(strict_types=1);

namespace App\Services\Post;

use Illuminate\Support\Facades\Cache;

class PostCacheService
{
    public function forgetAll(): void
    {
        Cache::forget('latest_posts');

        Cache::forget('featured_posts');

        Cache::forget('pinned_posts');
    }
}