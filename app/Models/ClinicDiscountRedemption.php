<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ClinicDiscountRedemption extends Model
{
    protected $fillable = [
        'discount_code_id','appointment_id','patient_id','doctor_id','original_amount_rial',
        'discount_amount_rial','final_amount_rial','applied_at',
    ];

    protected function casts(): array
    {
        return [
            'original_amount_rial'=>'integer','discount_amount_rial'=>'integer',
            'final_amount_rial'=>'integer','applied_at'=>'datetime',
        ];
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(ClinicDiscountCode::class, 'discount_code_id');
    }
}
