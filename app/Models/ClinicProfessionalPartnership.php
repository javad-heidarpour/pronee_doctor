<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicProfessionalPartnership extends Model
{
    protected $fillable = [
        'doctor_id','clinic_id','provider_id','created_by_user_id','model','service_name',
        'service_fee_rial','professional_share_bps','clinic_share_bps','fixed_fee_rial',
        'periodic_fee_rial','periodic_cycle','payment_direction','starts_at','ends_at',
        'status','accepted_at','terms',
    ];

    protected $casts = [
        'service_fee_rial' => 'integer',
        'professional_share_bps' => 'integer',
        'clinic_share_bps' => 'integer',
        'fixed_fee_rial' => 'integer',
        'periodic_fee_rial' => 'integer',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'accepted_at' => 'datetime',
    ];

    public function doctor(): BelongsTo { return $this->belongsTo(Doctor::class); }
    public function clinic(): BelongsTo { return $this->belongsTo(Address::class, 'clinic_id'); }
    public function provider(): BelongsTo { return $this->belongsTo(MedicalNetworkProvider::class, 'provider_id'); }
    public function sessions(): HasMany { return $this->hasMany(ClinicProfessionalSession::class, 'partnership_id'); }
    public function dues(): HasMany { return $this->hasMany(ClinicProfessionalDue::class, 'partnership_id'); }
}
