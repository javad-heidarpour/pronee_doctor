<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HealthExchangeMessage extends Model
{
    public const PENDING = 'pending';
    public const PROCESSING = 'processing';
    public const SENT = 'sent';
    public const FAILED = 'failed';
    public const BLOCKED = 'blocked';

    protected $fillable = [
        'doctor_id', 'patient_id', 'appointment_id', 'encounter_id', 'destination',
        'event_type', 'idempotency_key', 'status', 'payload', 'response_payload',
        'attempts', 'available_at', 'sent_at', 'remote_tracking_id', 'last_error',
    ];

    protected $casts = [
        'payload' => 'encrypted:array',
        'response_payload' => 'encrypted:array',
        'available_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(PatientEncounter::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
