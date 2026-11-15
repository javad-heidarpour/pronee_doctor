<?php

namespace App\Actions\Blog\Search;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchPostAction
{
    public function handle(Request $request): JsonResponse
    {

        $query = trim($request->input('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([
                'results' => [],
            ]);
        }

        $results = Post::published()
            ->search($query)
            ->with('category')
            ->latest()
            ->take(5)
            ->get()
            ->map(function (Post $post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'excerpt' => $post->excerpt,
                    'slug' => $post->slug,
                    'category' => $post->category?->name,
                    'url' => route('blog.show', $post),
                    'published_at' => $post->persian_published_at,
                ];
            });

        return response()->json([
            'results' => $results,
        ]);
    }
}