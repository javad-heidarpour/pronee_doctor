<?php

declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;

class PublishPostAction
{
    public function execute(Post $post): Post
    {
        $post->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        return $post->refresh();
    }
}