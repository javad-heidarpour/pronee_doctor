<?php

namespace App\Actions\Blog\Post;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class LoadRelatedPostsAction
{
    public function handle(Post $post): Collection
    {
        return Post::published()

            ->where('category_id', $post->category_id)

            ->whereKeyNot($post->id)

            ->with([
                'doctor',
                'category'
            ])

            ->latest()

            ->take(4)

            ->get();
    }
}