<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PatientAccountAccess extends Model
{
    protected $fillable = [
        'patient_id', 'user_id', 'granted_by_user_id', 'access_type',
        'permissions', 'expires_at', 'revoked_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function grantor(): BelongsTo { return $this->belongsTo(User::class, 'granted_by_user_id'); }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
