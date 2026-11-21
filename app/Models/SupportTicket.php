<?php

declare(strict_types=1);

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

final class SupportTicket extends Model
{
    protected $fillable = [
        'user_id', 'reference', 'name', 'email', 'category', 'subject',
        'message', 'status', 'priority', 'staff_response', 'responded_by', 'responded_at',
    ];

    protected $casts = ['responded_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }
}
