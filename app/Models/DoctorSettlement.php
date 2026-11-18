<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSettlement extends Model
{
    protected $fillable = ['organization_id','doctor_id','period_start','period_end','gross_amount','clinic_share_amount','adjustment_amount','net_amount','status','approved_by','paid_by','paid_at','payment_method','payment_reference','notes'];
    protected $casts = ['period_start' => 'date','period_end' => 'date','paid_at' => 'datetime'];
    public function doctor(): BelongsTo { return $this->belongsTo(Doctor::class); }
    public function organization(): BelongsTo { return $this->belongsTo(ClinicOrganization::class, 'organization_id'); }
}