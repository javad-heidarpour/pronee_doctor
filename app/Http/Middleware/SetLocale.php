<?php

declare(strict_types=1);

namespace App\\Http\\Middleware;

use Closure;
use Illuminate\\Http\\Request;
use Symfony\\Component\\HttpFoundation\\Response;

final class SetLocale
{
    public const SUPPORTED = ['fa', 'en', 'ar', 'tr'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', config('app.locale', 'fa'));
        if (!in_array($locale, self::SUPPORTED, true)) {
            $locale = 'fa';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
