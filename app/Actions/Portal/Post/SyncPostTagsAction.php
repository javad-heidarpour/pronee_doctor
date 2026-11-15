<?php

declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;

class SyncPostTagsAction
{
    public function execute(
        Post $post,
        array $tags
    ): void {

        $post->tags()->sync($tags);

    }
}