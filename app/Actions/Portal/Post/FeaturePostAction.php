<?php

declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;

class FeaturePostAction
{
    public function execute(Post $post): Post
    {
        $post->update([
            'is_featured' => true,
        ]);

        return $post->refresh();
    }
}