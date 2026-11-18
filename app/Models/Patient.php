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
}
