<?php

namespace App\Providers;

use App\Contracts\Sms\SmsGateway;
use App\Repositories\Contracts\DoctorRepositoryInterface;
use App\Repositories\Eloquent\DoctorRepository;
use App\Services\Sms\GhasedakSmsGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            DoctorRepositoryInterface::class,
            DoctorRepository::class,
        );

        $this->app->bind(SmsGateway::class, GhasedakSmsGateway::class);
    }

    public function boot(): void
    {
        //
    }
}
