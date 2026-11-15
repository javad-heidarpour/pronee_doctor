<?php

declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;
use App\Services\Post\ReadingTimeService;
use App\Services\Post\SlugService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdatePostAction
{
    public function execute(Post $post, array $data): Post
    {
        return DB::transaction(function () use ($post, $data) {

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

            if (
                isset($payload['title']) &&
                $payload['title'] !== $post->title
            ) {
                $payload['slug'] = SlugService::generate(
                    $payload['title']
                );
            }

            if (isset($payload['content'])) {
                $payload['reading_time'] =
                    ReadingTimeService::calculate(
                        $payload['content']
                    );
            }

            $post->update($payload);

            if (array_key_exists('tags', $data)) {
                $post->tags()->sync($data['tags'] ?? []);
            }

            return $post->fresh([
                'doctor',
                'author',
                'category',
                'tags',
            ]);
        });
    }
}