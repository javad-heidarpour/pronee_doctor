<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalNetworkProvider extends Model
{
    protected $fillable = [
        'created_by_user_id', 'organization_id', 'doctor_id', 'provider_type',
        'name', 'slug', 'city', 'address', 'phone', 'email', 'license_number',
        'specialties', 'services', 'description', 'status', 'is_verified',
        'rating', 'completed_referrals_count',
    ];

    protected $casts = [
        'specialties' => 'array',
        'services' => 'array',
        'is_verified' => 'boolean',
        'rating' => 'decimal:2',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(ClinicOrganization::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(MedicalReferral::class, 'target_provider_id');
    }
}
