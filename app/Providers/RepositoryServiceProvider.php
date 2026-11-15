<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Repositories\Contracts\DoctorRepositoryInterface;
use App\Repositories\Eloquent\DoctorRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(

            DoctorRepositoryInterface::class,

            DoctorRepository::class

        );
    }

    public function boot(): void
    {
    }
}