<?php

declare(strict_types=1);

namespace App\Services\Post;

class ReadingTimeService
{
    public static function calculate(
        string $content
    ): int {

        $words = str_word_count(
            strip_tags($content)
        );

        return max(
            1,
            (int) ceil($words / 220)
        );
    }
}