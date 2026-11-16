<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\VisitReason;
use Illuminate\Support\Facades\DB;
use App\Models\SurgeryType;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'clinic_id',
        'time_slot_id',
        'user_id',
        'patient_name',
        'patient_phone',
        'patient_national_code',
        'status',
        'appointment_date',
        'appointment_time',
        'visit_number',
        'fee_rial',
        'reservation_expires_at',
        'visit_reason_id',
        'description',

        // اطلاعات عمل جراحی
        'surgery_type_id',
        'surgery_date',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'contacted_at' => 'datetime',
        'appointment_date' => 'date:Y-m-d',
        'surgery_date' => 'date:Y-m-d',
        'reservation_expires_at' => 'datetime',

    ];



    public static function updateUnpaidAppointmentsFee(
        int $newFee,
        int $doctorId
    ): int {
        return self::query()
            ->where('doctor_id', $doctorId)
            ->where('status', 'pending')
            // پرداخت‌های در حال انجام یا موفق نباید با تعرفه جدید دست‌کاری شوند.
            ->whereDoesntHave('payments', function ($query) {
                $query->whereIn('status', ['pending', 'initiated', 'paid']);
            })
            ->update([
                'fee_rial' => $newFee,
            ]);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }



    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'clinic_id');
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'در انتظار',
            'contacted' => 'تماس گرفته شده',
            'confirmed' => 'تایید شده',
            'cancelled' => 'لغو شده',
            default => 'نامشخص',
        };
    }

    public function visitReason()
    {
        return $this->belongsTo(VisitReason::class);
    }





    public function payments(): HasMany
    {
        return $this->hasMany(
            AppointmentPayment::class,
            'appointment_id'
        );
    }


    public function latestPayment(): HasOne
    {
        return $this->hasOne(
            AppointmentPayment::class,
            'appointment_id'
        )->latestOfMany();
    }
    public function surgeryType(): BelongsTo
    {
        return $this->belongsTo(SurgeryType::class);
    }
}