<?php

declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;

class TogglePinnedPostAction
{
    public function execute(Post $post): Post
    {
        $post->update([
            'is_pinned' => ! $post->is_pinned,
        ]);

        return $post->refresh();
    }
}