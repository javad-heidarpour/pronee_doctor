<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ClinicDiscountCode extends Model
{
    protected $fillable = [
        'doctor_id','clinic_id','created_by_user_id','code','name','discount_type','discount_value',
        'minimum_amount_rial','maximum_discount_rial','usage_limit','per_patient_limit','status','starts_at','ends_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_value'=>'integer','minimum_amount_rial'=>'integer','maximum_discount_rial'=>'integer',
            'usage_limit'=>'integer','per_patient_limit'=>'integer','starts_at'=>'datetime','ends_at'=>'datetime',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(ClinicDiscountRedemption::class, 'discount_code_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'clinic_id');
    }
}
