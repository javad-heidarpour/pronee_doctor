<?php

namespace App\Actions\Blog\Listing;

use App\Models\Category;
use App\Models\Post;

class BlogIndexAction
{
    public function handle(): array
    {

        $featuredPosts = Post::published()
            ->featured()
            ->with('category')
            ->latest()
            ->take(3)
            ->get();

        $latestPosts = Post::published()
            ->with('category')
            ->latest()
            ->take(6)
            ->get();

        $categories = Category::active()
            ->ordered()
            ->withCount([
                'posts' => function ($query) {
                    $query->published();
                }
            ])
            ->get();

        return [
            'featuredPosts' => $featuredPosts,
            'latestPosts'   => $latestPosts,
            'categories'    => $categories,
        ];
    }
}