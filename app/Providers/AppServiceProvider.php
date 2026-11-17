<?php

namespace App\Providers;

use App\Repositories\Contracts\DoctorRepositoryInterface;
use App\Repositories\Eloquent\DoctorRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            DoctorRepositoryInterface::class,
            DoctorRepository::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
