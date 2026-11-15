<?php

declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class FeaturedPostsAction
{
    public function execute(
        int $limit = 6
    ): Collection {

        return Post::query()
            ->published()
            ->featured()
            ->latestPublished()
            ->take($limit)
            ->get();
    }
}