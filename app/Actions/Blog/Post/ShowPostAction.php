<?php

namespace App\Actions\Blog\Post;

use App\Models\Doctor;
use Inertia\Inertia;
use Inertia\Response;

class ShowPostAction
{
    /**
     * نمایش مقاله عمومی
     *
     * /blog/{post}
     */
    public function handle(Post $post): Response
    {

        if (!$post->isPublished()) {
            abort(404);
        }

        // افزایش بازدید
        $post->incrementViewsCount();

        // بارگذاری روابط
        $post->load([
            'doctor.Specialty',
            'category',
            'author',
            'tags',
            'approvedComments' => function ($query) {
                $query->with([
                    'replies' => function ($reply) {
                        $reply->approved()->latest();
                    }
                ]);
            },
        ]);

        // مقالات مرتبط
        $relatedPosts = Post::published()
            ->where('category_id', $post->category_id)
            ->whereKeyNot($post->id)
            ->latest()
            ->take(4)
            ->get();

        return Inertia::render('Blog/Show', [
            'post' => $post,
            'doctorPosts' => $doctorPosts,

            'commentsStats' => [
                'average' => $post->average_rating,
                'count' => $post->comments_count,
            ],
        ]);
    }

    /**
     * نمایش مقاله زیر پروفایل پزشک
     *
     * /{doctor}/blog/{post}
     */
    public function handleDoctorPost(
        Doctor $doctor,
        Post $post
    ): Response {




        if (!$post->isPublished()) {
            abort(404);
        }

        if ($post->doctor_id !== $doctor->id) {
            abort(404);
        }

        // افزایش بازدید
        $post->incrementViewsCount();

        // بارگذاری روابط
        $post->load([
            'doctor.Specialty',
            'category',
            'author',
            'tags',
            'approvedComments' => function ($query) {
                $query->with([
                    'replies' => function ($reply) {
                        $reply->approved()->latest();
                    }
                ]);
            },
        ]);

        // سایر مقالات پزشک
        $doctorPosts = Post::published()
            ->where('doctor_id', $doctor->id)
            ->whereKeyNot($post->id)
            ->latest()
            ->take(6)
            ->get();

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