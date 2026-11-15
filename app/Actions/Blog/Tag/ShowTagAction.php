<?php

namespace App\Actions\Blog\Tag;

use App\Models\Post;
use App\Models\Doctor;
use App\Models\Tag;
use Inertia\Inertia;
use Inertia\Response;

class ShowTagAction
{
    public function handle(
        Doctor $doctor,
        string $slug
    ): Response {
        $tag = Tag::where('slug', $slug)->first();
        $posts = null;

        if ($tag) {


            $posts = Post::published()
                ->where('doctor_id', $doctor->id)
                ->whereHas('tags', function ($query) use ($tag) {
                    $query->where('tags.id', $tag->id);
                })
                ->with([
                    'category',
                    'doctor',
                    'tags',
                ])
                ->latest()
                ->paginate(12);
        }
        return Inertia::render('Blog/Tag', [
            'doctor' => $doctor,
            'tag' => $tag,
            'slug' => $slug,
            'posts' => $posts,
        ]);
    }
}