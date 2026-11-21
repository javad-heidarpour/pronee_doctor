<?php

declare(strict_types=1);

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

final class AppointmentSatisfactionSurvey extends Model
{
    protected $fillable = [
        'appointment_id', 'doctor_id', 'overall_rating',
        'communication_rating', 'waiting_rating', 'facility_rating',
        'comment', 'is_anonymous',
    ];

    protected $casts = [
        'overall_rating' => 'integer',
        'communication_rating' => 'integer',
        'waiting_rating' => 'integer',
        'facility_rating' => 'integer',
        'is_anonymous' => 'boolean',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
