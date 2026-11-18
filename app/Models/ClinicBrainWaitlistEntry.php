<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ClinicBrainWaitlistEntry extends Model
{
    protected $fillable = [
        'doctor_id', 'clinic_id', 'patient_id', 'created_by',
        'preferred_from', 'preferred_until', 'preferred_period',
        'contact_consent', 'status', 'notes',
    ];

    protected $casts = [
        'preferred_from' => 'date:Y-m-d',
        'preferred_until' => 'date:Y-m-d',
        'contact_consent' => 'boolean',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'clinic_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
