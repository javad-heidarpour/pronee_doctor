<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ClinicCashRegisterSession extends Model
{
    protected $fillable = [
        'doctor_id','clinic_id','opened_by_user_id','closed_by_user_id',
        'opening_balance_rial','closing_count_rial','difference_rial','status',
        'opened_at','closed_at','opening_note','closing_note',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance_rial'=>'integer','closing_count_rial'=>'integer','difference_rial'=>'integer',
            'opened_at'=>'datetime','closed_at'=>'datetime',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ClinicCashTransaction::class, 'session_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'clinic_id');
    }
}
