<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TimeSlot extends Model
{
    protected $fillable = [
        'doctor_id', 'clinic_id', 'date', 'start_time', 'end_time', 'status'
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    // اگر مدل مطب شما Address است
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'clinic_id');
    }


    // ✅ رابطه جدید: هر تایم اسلات می‌تواند یک نوبت داشته باشد
    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }

    // ✅ متد کمکی: بررسی اینکه آیا این تایم رزرو شده است
    public function isBooked(): bool
    {
        return $this->status === 'booked' || $this->appointment()->exists();
    }
}