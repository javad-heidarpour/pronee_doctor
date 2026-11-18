<?php

declare(strict_types=1);

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class MedicalReferralSettlement extends Model
{
    protected $fillable = ['contract_id', 'referral_id', 'amount_rial', 'status', 'paid_at', 'paid_by_user_id'];
    protected $casts = ['amount_rial' => 'integer', 'paid_at' => 'datetime'];

    public function contract(): BelongsTo { return $this->belongsTo(MedicalCollaborationContract::class, 'contract_id'); }
    public function referral(): BelongsTo { return $this->belongsTo(MedicalReferral::class, 'referral_id'); }
    public function paidBy(): BelongsTo { return $this->belongsTo(User::class, 'paid_by_user_id'); }
}
