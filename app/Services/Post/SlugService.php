<?php

declare(strict_types=1);

namespace App\Services\Post;

use App\Models\Post;
use Illuminate\Support\Str;

class SlugService
{
    public static function generate(string $title): string
    {
        $slug = Str::slug($title);

        $original = $slug;

        $i = 1;

        while (
            Post::whereSlug($slug)->exists()
        ) {

            $slug = "{$original}-{$i}";

            $i++;

        }

        return $slug;
    }
}