<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicOrganization extends Model
{
    protected $fillable = ['owner_user_id','name','slug','legal_name','registration_number','phone','email','timezone','status','settings'];
    protected $casts = ['settings' => 'array'];
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_user_id'); }
    public function doctors(): BelongsToMany { return $this->belongsToMany(Doctor::class, 'clinic_organization_doctors')->withPivot(['role','revenue_share_basis_points','status','joined_at'])->withTimestamps(); }
    public function clinics(): BelongsToMany { return $this->belongsToMany(Address::class, 'clinic_organization_clinics', 'organization_id', 'clinic_id')->withPivot(['branch_code','is_primary','status'])->withTimestamps(); }
    public function members(): BelongsToMany { return $this->belongsToMany(User::class, 'clinic_organization_members')->withPivot(['role','permissions','clinic_ids','is_active'])->withTimestamps(); }
    public function services(): HasMany { return $this->hasMany(ClinicService::class, 'organization_id'); }
}