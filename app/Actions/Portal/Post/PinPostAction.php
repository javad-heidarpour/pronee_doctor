<?php

declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;

class PinPostAction
{
    public function execute(Post $post): Post
    {
        $post->update([
            'is_pinned' => true,
        ]);

        return $post->refresh();
    }
}