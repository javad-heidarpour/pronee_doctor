<?php

declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;

class IncrementPostViewsAction
{
    public function execute(Post $post): void
    {
        $post->increment('views_count');
    }
}