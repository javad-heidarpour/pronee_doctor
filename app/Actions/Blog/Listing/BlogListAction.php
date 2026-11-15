<?php

namespace App\Actions\Blog\Listing;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogListAction
{
    public function handle(Request $request): Response
    {

        $query = Post::published()
            ->with([
                'category',
                'tags',
                'author',
            ]);

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('date_from')) {
            $query->where('published_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('published_at', '<=', $request->date_to);
        }

        switch ($request->input('sort', 'latest')) {

            case 'popular':
                $query->orderBy('views_count', 'desc');
                break;

            case 'pinned':
                $query->orderBy('is_pinned', 'desc')
                    ->latest();
                break;

            default:
                $query->latest();
                break;
        }

        $posts = $query
            ->paginate(10)
            ->withQueryString();

        $categories = Category::active()
            ->ordered()
            ->withCount([
                'posts' => function ($query) {
                    $query->published();
                }
            ])
            ->get();

        $tags = Tag::active()
            ->ordered()
            ->withCount([
                'posts' => function ($query) {
                    $query->published();
                }
            ])
            ->get();

        return Inertia::render('Blog/Index', [

            'posts' => $posts,

            'categories' => $categories,

            'tags' => $tags,

            'filters' => $request->only([
                'category',
                'tag',
                'search',
                'date_from',
                'date_to',
                'sort',
            ]),

        ]);
    }
}