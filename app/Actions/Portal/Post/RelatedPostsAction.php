<?php

declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class RelatedPostsAction
{
    public function execute(
        Post $post,
        int $limit = 6
    ): Collection {

        return Post::query()
            ->published()
            ->byCategory($post->category_id)
            ->exclude($post->id)
            ->latestPublished()
            ->take($limit)
            ->get();
    }
}