<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientInstallmentPayment extends Model
{
    protected $fillable = ['installment_id', 'received_by', 'amount', 'method', 'reference', 'paid_at', 'notes'];
    protected $casts = ['amount' => 'integer', 'paid_at' => 'datetime'];
    public function installment(): BelongsTo { return $this->belongsTo(PatientTreatmentInstallment::class, 'installment_id'); }
}