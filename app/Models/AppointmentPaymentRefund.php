<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AppointmentPaymentRefund extends Model
{
    protected $fillable = [
        'appointment_payment_id', 'requested_by_user_id', 'processed_by_user_id',
        'amount_rial', 'reason', 'status', 'gateway_ref_num', 'gateway_terminal_id',
        'gateway_result_code', 'gateway_result_message', 'gateway_response',
        'idempotency_key', 'requested_at', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_rial' => 'integer',
            'gateway_response' => 'encrypted:array',
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(AppointmentPayment::class, 'appointment_payment_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }
}
