<?php

namespace App\Models;
// app/Models/DoctorWorkingHour.php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorWorkingHour extends Model
{
    protected $fillable = [
        'doctor_id',
        'clinic_id',
        'day_of_week',
        'start_time',
        'end_time',
        'duration_minutes',
        'is_active',
    ];


    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'clinic_id');
    }

    // نام فارسی روز هفته
    public function getDayNameAttribute(): string
    {
        return [
            0 => 'شنبه',
            1 => 'یکشنبه',
            2 => 'دوشنبه',
            3 => 'سه‌شنبه',
            4 => 'چهارشنبه',
            5 => 'پنجشنبه',
            6 => 'جمعه',
        ][$this->day_of_week] ?? '';
    }

  



    // ✅ فرمت ساعت به فارسی (مثلاً "16:00" → "۱۶:۰۰")
    public function getFormattedStartTimeAttribute(): string
    {
        return $this->convertToPersian(substr($this->start_time, 0, 5));
    }

    public function getFormattedEndTimeAttribute(): string
    {
        return $this->convertToPersian(substr($this->end_time, 0, 5));
    }

    // ✅ تبدیل اعداد انگلیسی به فارسی
    private function convertToPersian(string $text): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($english, $persian, $text);
    }
}