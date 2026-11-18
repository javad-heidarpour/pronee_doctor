<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicService extends Model
{
    protected $fillable = ['organization_id','clinic_id','code','name','specialty_module','default_duration_minutes','default_price','doctor_share_basis_points','requires_follow_up','follow_up_after_days','is_active'];
    protected $casts = ['default_price' => 'integer','requires_follow_up' => 'boolean','is_active' => 'boolean'];
    public function organization(): BelongsTo { return $this->belongsTo(ClinicOrganization::class, 'organization_id'); }
    public function clinic(): BelongsTo { return $this->belongsTo(Address::class, 'clinic_id'); }
    public function inventoryRequirements(): HasMany { return $this->hasMany(ClinicServiceInventoryRequirement::class, 'service_id')->with('inventoryItem'); }
    public function treatmentItems(): HasMany { return $this->hasMany(PatientTreatmentItem::class, 'service_id'); }
}