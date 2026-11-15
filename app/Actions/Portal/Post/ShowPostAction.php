<?php

declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;

class ShowPostAction
{
    public function execute(Post $post): Post
    {
        return $post->load([
            'doctor',
            'author',
            'category',
            'tags',
            'approvedComments.user',
        ]);
    }
}