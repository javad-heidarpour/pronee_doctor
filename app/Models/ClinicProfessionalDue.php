<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicProfessionalDue extends Model
{
    protected $hidden = ['clinic_user_id', 'professional_user_id', 'created_by_user_id', 'payer_confirmed_by_user_id', 'receiver_confirmed_by_user_id'];

    protected $fillable = [
        'partnership_id', 'doctor_id', 'clinic_id', 'provider_id',
        'clinic_user_id', 'professional_user_id', 'created_by_user_id',
        'period_start', 'period_end', 'due_date', 'amount_rial',
        'payment_direction', 'status', 'payer_confirmed_at',
        'payer_confirmed_by_user_id', 'payer_reference',
        'receiver_confirmed_at', 'receiver_confirmed_by_user_id', 'receiver_reference',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'amount_rial' => 'integer',
        'payer_confirmed_at' => 'datetime',
        'receiver_confirmed_at' => 'datetime',
    ];

    public function partnership(): BelongsTo { return $this->belongsTo(ClinicProfessionalPartnership::class, 'partnership_id'); }
    public function doctor(): BelongsTo { return $this->belongsTo(Doctor::class); }
    public function clinic(): BelongsTo { return $this->belongsTo(Address::class, 'clinic_id'); }
    public function provider(): BelongsTo { return $this->belongsTo(MedicalNetworkProvider::class, 'provider_id'); }
    public function clinicUser(): BelongsTo { return $this->belongsTo(User::class, 'clinic_user_id'); }
    public function professionalUser(): BelongsTo { return $this->belongsTo(User::class, 'professional_user_id'); }
}
