<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Post;
use App\Observers\Post\PostObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Post::observe(PostObserver::class);

        Vite::prefetch(concurrency: 3);

        RateLimiter::for('post-comments', function (Request $request) {

            return [

                // هر IP هر 10 دقیقه حداکثر 3 نظر
                Limit::perMinutes(10, 3)
                    ->by($request->ip()),

            ];
        });
    }
}
