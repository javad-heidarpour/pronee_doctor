<?php

declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;

class UnPublishPostAction
{
    public function execute(Post $post): Post
    {
        $post->update([
            'is_published' => false,
        ]);

        return $post->refresh();
    }
}