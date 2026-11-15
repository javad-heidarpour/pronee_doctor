<?php

namespace App\Actions\Blog\Post;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class LoadDoctorPostsAction
{
    public function handle(Post $post): Collection
    {
        return Post::published()

            ->where('doctor_id', $post->doctor_id)

            ->whereKeyNot($post->id)

            ->with('category')

            ->latest()

            ->take(6)

            ->get();
    }
}