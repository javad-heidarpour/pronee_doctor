<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalCollaborationContract extends Model
{
    protected $fillable = [
        'doctor_id', 'provider_id', 'created_by_user_id', 'service_type',
        'unit_fee_rial', 'starts_at', 'ends_at', 'status', 'accepted_at', 'terms',
    ];

    protected $casts = [
        'unit_fee_rial' => 'integer',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'accepted_at' => 'datetime',
    ];

    public function doctor(): BelongsTo { return $this->belongsTo(Doctor::class); }
    public function provider(): BelongsTo { return $this->belongsTo(MedicalNetworkProvider::class, 'provider_id'); }
    public function settlements(): HasMany { return $this->hasMany(MedicalReferralSettlement::class, 'contract_id'); }
}
