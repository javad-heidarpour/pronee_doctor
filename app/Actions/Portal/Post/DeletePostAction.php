<?php

declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;
use Illuminate\Support\Facades\DB;

class DeletePostAction
{
    public function execute(Post $post): void
    {
        DB::transaction(function () use ($post) {

            $post->tags()->detach();

            $post->delete();

        });
    }
}