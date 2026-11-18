<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PatientVitalSign extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'measurement_type',
        'value',
        'unit',
        'measured_at',
        'note',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'measured_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
