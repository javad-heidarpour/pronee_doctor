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
        'contact_consent', 'status', 'notes', 'offered_slot_id', 'offered_at', 'fulfilled_appointment_id',
    ];

    protected $casts = [
        'preferred_from' => 'date:Y-m-d',
        'preferred_until' => 'date:Y-m-d',
        'contact_consent' => 'boolean',
        'offered_at' => 'datetime',
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

    public function offeredSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class, 'offered_slot_id');
    }

    public function fulfilledAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'fulfilled_appointment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
