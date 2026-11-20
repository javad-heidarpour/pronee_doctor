<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ClinicCashTransaction extends Model
{
    protected $fillable = [
        'session_id','doctor_id','clinic_id','created_by_user_id','direction','category',
        'amount_rial','description','idempotency_key',
    ];

    protected function casts(): array
    {
        return ['amount_rial'=>'integer'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClinicCashRegisterSession::class, 'session_id');
    }
}
