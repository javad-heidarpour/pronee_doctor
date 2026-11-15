<?php

namespace App\Actions\Blog\Post;

use App\Models\Doctor;
use Inertia\Inertia;
use Inertia\Response;

class LoadPostAction
{
    public function handle(Post $post): Response
    {
        if (!$post->isPublished()) {
            abort(404);
        }

        app(IncrementPostViewAction::class)
            ->handle($post);

        $post = app(LoadPostAction::class)
            ->handle($post);

        $relatedPosts = app(LoadRelatedPostsAction::class)
            ->handle($post);

        return Inertia::render('Blog/Show', [

            'post' => $post,

            'relatedPosts' => $relatedPosts,

            'commentsStats' => [

                'average' => $post->average_rating,

                'count' => $post->comments_count,

            ],

        ]);
    }

    public function handleDoctorPost(
        Doctor $doctor,
        Post $post
    ): Response {

        if (!$post->isPublished()) {
            abort(404);
        }

        app(IncrementPostViewAction::class)
            ->handle($post);

        $post = app(LoadPostAction::class)
            ->handle($post, $doctor);

        $doctorPosts = app(LoadDoctorPostsAction::class)
            ->handle($post);

        return Inertia::render('Blog/Show', [

            'post' => $post,

            'doctorPosts' => $doctorPosts,

            'commentsStats' => [

                'average' => $post->average_rating,

                'count' => $post->comments_count,

            ],

        ]);
    }
}