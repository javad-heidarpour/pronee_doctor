<?php

namespace App\Actions\Blog\Post;

use App\Models\Post;

class IncrementPostViewAction
{
    /**
     * افزایش تعداد بازدید مقاله
     */
    public function handle(Post $post): void
    {
        $post->incrementViewsCount();
    }
}