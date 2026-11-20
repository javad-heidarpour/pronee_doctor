<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ClinicBusinessLead extends Model
{
    protected $fillable = [
        'doctor_id','clinic_id','campaign_id','created_by_user_id','converted_appointment_id',
        'name','phone','email','source','status','contact_consent','consent_at',
        'next_follow_up_at','notes','converted_at',
    ];

    protected function casts(): array
    {
        return [
            'phone'=>'encrypted','email'=>'encrypted','contact_consent'=>'boolean',
            'consent_at'=>'datetime','next_follow_up_at'=>'datetime','converted_at'=>'datetime',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'clinic_id');
    }
}
