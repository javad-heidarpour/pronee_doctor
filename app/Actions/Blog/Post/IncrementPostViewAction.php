<?php

namespace App\Actions\Blog\Post;


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