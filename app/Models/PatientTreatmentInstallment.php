<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientTreatmentInstallment extends Model
{
    protected $fillable = ['treatment_plan_id', 'sequence', 'amount', 'paid_amount', 'due_date', 'status', 'last_reminded_at'];
    protected $casts = ['amount' => 'integer', 'paid_amount' => 'integer', 'due_date' => 'date', 'last_reminded_at' => 'datetime'];
    public function plan(): BelongsTo { return $this->belongsTo(PatientTreatmentPlan::class, 'treatment_plan_id'); }
    public function payments(): HasMany { return $this->hasMany(PatientInstallmentPayment::class, 'installment_id'); }
}