<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Doctor;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'is_active',
        'family',
        'mobile',
        'username',

    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function doctorProfile(): HasOne
    {
        return $this->hasOne(Doctor::class, 'user_id');
    }
    /**
     * پزشکان مرتبط با این منشی
     *//**
     * پزشکانی که این کاربر به آن‌ها دسترسی دارد
     */
    /**
     * پزشکانی که این منشی به آن‌ها دسترسی دارد
     */
    public function doctors()
    {
        return $this->belongsToMany(
            Doctor::class,
            'doctor_user',
            'user_id',
            'doctor_id'
        )
            ->withPivot('permissions')
            ->withTimestamps();
    }

    // مالکیت های پرونده پزشک یعنی این سایت مال کدوم کاربر است
    public function ownedDoctorProfiles()
    {
        return $this->hasMany(Doctor::class, 'user_id');
    }
    /**
     * منشی‌های این پزشک
     */

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'user_id');
    }

    public function clinics()
    {
        return $this->belongsToMany(Clinic::class, 'clinic_user')
            ->withPivot('is_active');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }

    public function isSecretary(): bool
    {
        return $this->role === 'secretary';
    }
}