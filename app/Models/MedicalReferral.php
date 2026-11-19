<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MedicalReferral extends Model
{
    protected $fillable = [
        'tracking_code', 'patient_id', 'referring_doctor_id', 'source_clinic_id', 'target_provider_id',
        'created_by_user_id', 'service_type', 'priority', 'status', 'reason',
        'clinical_summary', 'patient_message', 'consent_at', 'accepted_at', 'scheduled_at',
        'completed_at', 'provider_response', 'result_summary', 'patient_instructions', 'cancelled_at',
    ];

    protected $casts = [
        'consent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function referringDoctor(): BelongsTo { return $this->belongsTo(Doctor::class, 'referring_doctor_id'); }
    public function sourceClinic(): BelongsTo { return $this->belongsTo(Address::class, 'source_clinic_id'); }
    public function targetProvider(): BelongsTo { return $this->belongsTo(MedicalNetworkProvider::class, 'target_provider_id'); }
    public function events(): HasMany { return $this->hasMany(MedicalReferralEvent::class); }
    public function settlement(): HasOne { return $this->hasOne(MedicalReferralSettlement::class, 'referral_id'); }
}
