<?php

declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;
use App\Services\Post\ReadingTimeService;
use App\Services\Post\SlugService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreatePostAction
{
    public function execute(array $data): Post
    {
        return DB::transaction(function () use ($data) {

            $payload = Arr::only($data, [
                'category_id',
                'title',
                'excerpt',
                'content',
                'featured_image',
                'meta_title',
                'meta_description',
                'canonical_url',
                'keywords',
                'published_at',
                'is_published',
                'is_featured',
                'is_pinned',
            ]);

            $payload['doctor_id'] = auth('web')->id();
            $payload['author_id'] = auth()->id();

            $payload['slug'] = SlugService::generate(
                $payload['title']
            );

            $payload['reading_time'] =
                ReadingTimeService::calculate(
                    $payload['content']
                );

            $post = Post::create($payload);

            if (!empty($data['tags'])) {
                $post->tags()->sync($data['tags']);
            }

            return $post;
        });
    }
}