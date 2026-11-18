<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // ✅ این خط باید وجود داشته باشد

use App\Models\City;
use App\Models\TimeSlot;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'Longitude',
        'Latitude',
        'Altitude',
        'is_active',
        'doctor_id',
        'organization_id',
        'full_address',
        'city_id',
        'phone',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the doctors for the city.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(ClinicOrganization::class, 'organization_id');
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class, 'clinic_id');
    }
    public function workingHours(): HasMany
{
    return $this->hasMany(DoctorWorkingHour::class, 'clinic_id');
}
    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id'); // اطمینان از نام صحیح ستون
    }
    /**
     * Scope: فقط شهرهای فعال
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}