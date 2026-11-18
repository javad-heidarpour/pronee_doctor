<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientTreatmentPlan extends Model
{
    protected $fillable = [
        'doctor_id', 'clinic_id', 'patient_id', 'created_by', 'title',
        'specialty_module', 'status', 'total_amount', 'discount_amount',
        'deposit_amount', 'installment_count', 'clinical_notes',
        'organization_id', 'financial_terms', 'sms_reminders_enabled', 'sms_consent_confirmed', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'total_amount' => 'integer', 'discount_amount' => 'integer',
        'deposit_amount' => 'integer', 'installment_count' => 'integer',
        'started_at' => 'date', 'completed_at' => 'date',
        'sms_reminders_enabled' => 'boolean', 'sms_consent_confirmed' => 'boolean',
    ];

    public function organization(): BelongsTo { return $this->belongsTo(ClinicOrganization::class, 'organization_id'); }\n    public function items(): HasMany { return $this->hasMany(PatientTreatmentItem::class, 'treatment_plan_id')->orderBy('sequence'); }\n\n    public function doctor(): BelongsTo { return $this->belongsTo(Doctor::class); }
    public function clinic(): BelongsTo { return $this->belongsTo(Address::class, 'clinic_id'); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function installments(): HasMany { return $this->hasMany(PatientTreatmentInstallment::class, 'treatment_plan_id')->orderBy('sequence'); }
    public function collaterals(): HasMany { return $this->hasMany(PatientCollateral::class, 'treatment_plan_id'); }

    public function getNetAmountAttribute(): int
    {
        return max(0, $this->total_amount - $this->discount_amount);
    }
}