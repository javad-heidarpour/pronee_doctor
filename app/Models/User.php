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
        $query = Doctor::query()
            ->where('doctors.is_active', true);

        if ($this->role === 'admin') {
            return $query;
        }

        if ($this->role === 'doctor') {
            return $query->where(function (Builder $access) {
                $access->where('doctors.user_id', $this->id)
                    ->orWhereIn(
                        'doctors.id',
                        $this->doctors()->select('doctors.id')
                    );
            });
        }

        if ($this->role === 'secretary') {
            return $query->whereIn(
                'doctors.id',
                $this->doctors()->select('doctors.id')
            );
        }

        return $query->whereRaw('1 = 0');
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
        )->withPivot('permissions', 'clinic_ids')->withTimestamps();
    }

    public function ownedDoctorProfiles(): HasMany
    {
        return $this->hasMany(Doctor::class, 'user_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'user_id');
    }

    /**
     * مطب‌های متعلق به پزشکانی که این حساب به آن‌ها دسترسی دارد.
     *
     * در ساختار فعلی پروژه، مطب‌ها در جدول addresses نگهداری می‌شوند
     * و هر مطب با doctor_id به یک پزشک متصل است؛ مدل Clinic و جدول
     * clinic_user در مخزن پروژه تعریف نشده‌اند.
     */
    public function accessibleClinics(): Builder
    {
        if ($this->role === 'admin' || $this->role === 'doctor') {
            return Address::query()->whereIn(
                'addresses.doctor_id',
                $this->accessibleDoctors()->select('doctors.id')
            );
        }

        if ($this->role !== 'secretary') {
            return Address::query()->whereRaw('1 = 0');
        }

        $clinicIds = $this->doctors()
            ->where('doctors.id', session('active_doctor_id'))
            ->get()
            ->flatMap(function ($doctor) {
                $ids = $doctor->pivot?->clinic_ids ?? [];

                if (is_string($ids)) {
                    $ids = json_decode($ids, true) ?: [];
                }

                return is_array($ids) ? $ids : [];
            })
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return Address::query()
            ->whereIn('addresses.doctor_id', $this->accessibleDoctors()->select('doctors.id'))
            ->whereIn('addresses.id', $clinicIds)
            ->where('addresses.is_active', true);
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