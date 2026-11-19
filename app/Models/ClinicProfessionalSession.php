<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicProfessionalSession extends Model
{
    protected $fillable = [
        'partnership_id','doctor_id','clinic_id','provider_id','appointment_id','created_by_user_id',
        'service_name','service_date','gross_amount_rial','adjustment_amount_rial',
        'professional_amount_rial','clinic_amount_rial','status','paid_at','paid_by_user_id',
        'payment_reference','notes',
    ];

    protected $casts = [
        'service_date' => 'date',
        'gross_amount_rial' => 'integer',
        'adjustment_amount_rial' => 'integer',
        'professional_amount_rial' => 'integer',
        'clinic_amount_rial' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function partnership(): BelongsTo { return $this->belongsTo(ClinicProfessionalPartnership::class, 'partnership_id'); }
    public function doctor(): BelongsTo { return $this->belongsTo(Doctor::class); }
    public function clinic(): BelongsTo { return $this->belongsTo(Address::class, 'clinic_id'); }
    public function provider(): BelongsTo { return $this->belongsTo(MedicalNetworkProvider::class, 'provider_id'); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
}
