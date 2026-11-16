<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

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
     * پرونده‌های پزشکانی که این حساب مجاز به مدیریت آن‌هاست.
     * پزشک: مالک پرونده از طریق doctors.user_id
     * منشی: پرونده‌های متصل از طریق doctor_user
     * مدیر: تمام پرونده‌های فعال
     */
    public function accessibleDoctors(): Builder
    {
        return Doctor::query()
            ->where('doctors.is_active', true)
            ->where(function (Builder $query) {
                if ($this->role === 'admin') {
                    return;
                }

                if ($this->role === 'doctor') {
                    $query->where(function (Builder $access) {
                        $access->where('doctors.user_id', $this->id)
                            ->orWhereIn(
                                'doctors.id',
                                $this->doctors()->select('doctors.id')
                            );
                    });
                    return;
                }

                if ($this->role === 'secretary') {
                    $query->whereIn(
                        'doctors.id',
                        $this->doctors()->select('doctors.id')
                    );
                    return;
                }

                $query->whereRaw('1 = 0');
            });
    }

    /**
     * پزشکانی که این کاربر به آن‌ها دسترسی دارد (منشی‌ها).
     */
    public function doctors()
    {
        return $this->belongsToMany(
            Doctor::class,
            'doctor_user',
            'user_id',
            'doctor_id'
        )->withPivot('permissions')->withTimestamps();
    }

    public function ownedDoctorProfiles(): HasMany
    {
        return $this->hasMany(Doctor::class, 'user_id');
    }

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