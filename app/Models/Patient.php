<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'doctor_id',
        'name',
        'phone',
        'national_code',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function dentalChartEntries(): HasMany
    {
        return $this->hasMany(PatientDentalChartEntry::class)->orderByDesc('recorded_at');
    }

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(PatientTreatmentPlan::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(PatientVitalSign::class)
            ->orderByDesc('measured_at')
            ->orderByDesc('id')
            ->limit(100);
    }

    public function clinicalRecords(): HasMany
    {
        return $this->hasMany(PatientClinicalRecord::class)
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function archivedClinicalRecords(): HasMany
    {
        return $this->hasMany(PatientClinicalRecord::class)
            ->onlyTrashed()
            ->orderByDesc('deleted_at')
            ->orderByDesc('id');
    }

    public function problems(): HasMany
    {
        return $this->hasMany(PatientProblem::class)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'resolved' THEN 1 ELSE 2 END")
            ->orderByDesc('onset_date')
            ->orderByDesc('id');
    }
}
