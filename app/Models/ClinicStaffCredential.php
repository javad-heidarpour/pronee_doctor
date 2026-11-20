<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ClinicStaffCredential extends Model
{
    protected $fillable = [
        'organization_id', 'clinic_id', 'staff_user_id', 'uploaded_by_user_id',
        'credential_type', 'title', 'issuer', 'license_number', 'issued_at',
        'expires_at', 'original_name', 'disk', 'path', 'mime_type', 'file_size',
        'checksum_sha256', 'status', 'verified_by_user_id', 'verified_at',
        'rejection_reason', 'archived_by_user_id', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'verified_at' => 'datetime',
            'archived_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(ClinicOrganization::class, 'organization_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'clinic_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lt(today());
    }
}
