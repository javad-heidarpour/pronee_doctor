<?php

namespace App\Providers;

use App\Contracts\Sms\SmsGateway;
use App\Models\Appointment;
use App\Observers\AppointmentAuditObserver;
use App\Observers\PatientAuditObserver;
use App\Observers\PatientClinicalRecordObserver;
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
        Appointment::observe(AppointmentAuditObserver::class);
        \App\Models\Patient::observe(PatientAuditObserver::class);
        \App\Models\PatientClinicalRecord::observe(PatientClinicalRecordObserver::class);
    }
}
