<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PatientInsuranceVerification extends Model
{
    protected $fillable = [
        'doctor_id', 'patient_id', 'appointment_id', 'payer_code', 'payer_name',
        'policy_number', 'member_number', 'status', 'checked_at', 'valid_from',
        'valid_until', 'tracking_id', 'response_payload', 'failure_code', 'failure_message',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'response_payload' => 'encrypted:array',
        'policy_number' => 'encrypted',
        'member_number' => 'encrypted',
    ];
}
