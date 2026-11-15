<?php

declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class LatestPostsAction
{
    public function execute(
        int $limit = 10
    ): Collection {

        return Post::query()
            ->published()
            ->latestPublished()
            ->take($limit)
            ->get();
    }
}