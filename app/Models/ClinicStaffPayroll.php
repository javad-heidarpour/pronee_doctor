<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicStaffPayroll extends Model
{
    protected $fillable = [
        'organization_id','clinic_id','staff_user_id','period_start','period_end',
        'gross_amount','deduction_amount','net_amount','worked_minutes','scheduled_minutes',
        'paid_leave_minutes','overtime_minutes','base_pay','overtime_pay','allowance_amount',
        'calculation_snapshot','status','approved_by','paid_by','paid_at','payment_method',
        'payment_reference','notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'paid_at' => 'datetime',
        'calculation_snapshot' => 'array',
    ];

    public function staff(): BelongsTo { return $this->belongsTo(User::class, 'staff_user_id'); }
    public function organization(): BelongsTo { return $this->belongsTo(ClinicOrganization::class, 'organization_id'); }
    public function clinic(): BelongsTo { return $this->belongsTo(Address::class, 'clinic_id'); }
}
