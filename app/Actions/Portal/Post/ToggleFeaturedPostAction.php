<?php

declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;

class ToggleFeaturedPostAction
{
    public function execute(Post $post): Post
    {
        $post->update([
            'is_featured' => ! $post->is_featured,
        ]);

        return $post->refresh();
    }
}