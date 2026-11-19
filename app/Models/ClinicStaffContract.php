<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicStaffContract extends Model
{
    protected $fillable = [
        'organization_id','clinic_id','staff_user_id','compensation_type',
        'base_monthly_salary','hourly_rate','standard_monthly_minutes',
        'overtime_multiplier_basis_points','annual_leave_days','paid_leave_types',
        'effective_from','effective_until','is_active','notes','created_by',
    ];

    protected $casts = [
        'paid_leave_types'=>'array',
        'effective_from'=>'date',
        'effective_until'=>'date',
        'is_active'=>'boolean',
        'annual_leave_days'=>'decimal:2',
    ];

    public function organization(): BelongsTo { return $this->belongsTo(ClinicOrganization::class,'organization_id'); }
    public function clinic(): BelongsTo { return $this->belongsTo(Address::class,'clinic_id'); }
    public function staff(): BelongsTo { return $this->belongsTo(User::class,'staff_user_id'); }
}
