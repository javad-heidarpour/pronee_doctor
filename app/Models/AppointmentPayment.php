<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentPayment extends Model
{
    protected $fillable = [
        'appointment_id',
        'amount_rial',
        'status',
        'payment_uuid',
        'gateway_token',
        'ref_num',
        'terminal_id',
        'gateway_result_code',
        'gateway_result_message',
        'gateway_name',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_rial' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}