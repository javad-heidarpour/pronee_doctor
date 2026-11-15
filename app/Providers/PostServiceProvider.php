<?php
declare(strict_types=1);

namespace App\Providers;

use App\Models\Post;
use App\Observers\Post\PostObserver;
use App\Policies\Post\PostPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PostServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ثبت Observer
        Post::observe(PostObserver::class);

        // ثبت Policy
        Gate::policy(Post::class, PostPolicy::class);
    }
}