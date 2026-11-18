<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicStaffPayroll extends Model
{
    protected $fillable = ['organization_id','clinic_id','staff_user_id','period_start','period_end','gross_amount','deduction_amount','net_amount','status','approved_by','paid_by','paid_at','notes'];
    protected $casts = ['period_start' => 'date','period_end' => 'date','paid_at' => 'datetime'];
    public function staff(): BelongsTo { return $this->belongsTo(User::class, 'staff_user_id'); }
    public function organization(): BelongsTo { return $this->belongsTo(ClinicOrganization::class, 'organization_id'); }
}