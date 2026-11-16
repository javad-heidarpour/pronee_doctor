<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Doctor;
use App\Models\DoctorImage;
use App\Policies\DoctorPolicy;
use App\Policies\DoctorImagePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [

        Doctor::class => DoctorPolicy::class,
        DoctorImage::class => DoctorImagePolicy::class,

    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}