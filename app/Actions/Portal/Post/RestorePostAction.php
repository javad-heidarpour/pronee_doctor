<?php

declare(strict_types=1);

namespace App\Actions\Portal\Post;

use App\Models\Post;
use Illuminate\Support\Facades\DB;

class RestorePostAction
{
    public function execute(Post $post): Post
    {
        DB::transaction(function () use ($post) {

            $post->restore();

        });

        return $post;
    }
}