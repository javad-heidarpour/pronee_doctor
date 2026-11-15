<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Menu extends Model
{
    protected $fillable = [
        'title',
        'route_name',
        'url',
        'icon',
        'order',
        'guard',
        'is_active',
        'show_in_sidebar',
        'permissions',
        'roles',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_sidebar' => 'boolean',
        'order' => 'integer',
        'permissions' => 'array',
        'roles' => 'array',
    ];

    public function scopeForGuard(Builder $query, string $guard = 'doctor')
    {
        return $query
            ->where('guard', $guard)
            ->where('is_active', true)
            ->where('show_in_sidebar', true)
            ->orderBy('order');
    }

    /**
     * بررسی دسترسی کاربر بر اساس Role و Permission
     */
    public function hasPermission($user, ?int $doctorId = null): bool
    {
        if (!$user) {
            return false;
        }

        // مدیر سامانه مستقل از پزشک و مجوزهای pivot است.
        if ($user->role === 'admin') {
            return true;
        }

        // بررسی نقش‌های مجاز برای مشاهده منو
        $roles = $this->roles ?? [];

        if (is_string($roles)) {
            $roles = json_decode($roles, true) ?? [];
        }

        if (!empty($roles) && !in_array($user->role, $roles, true)) {
            return false;
        }

        // پزشک به منوهای مجاز نقش خودش دسترسی دارد.
        if ($user->role === 'doctor') {
            return true;
        }

        // فقط پزشک و منشی در این ساختار پشتیبانی می‌شوند.
        if ($user->role !== 'secretary') {
            return false;
        }

        // منشی باید پزشک فعال داشته باشد.
        if (!$doctorId) {
            return false;
        }

        // بررسی اتصال منشی به پزشک انتخاب‌شده
        $doctor = $user->doctors()
            ->where('doctors.id', $doctorId)
            ->where('doctors.is_active', true)
            ->first();

        if (!$doctor) {
            return false;
        }

        // مجوزهای لازم برای مشاهده این منو
        $menuPermissions = $this->permissions ?? [];

        if (is_string($menuPermissions)) {
            $menuPermissions = json_decode($menuPermissions, true) ?? [];
        }

        // اگر منو مجوز اختصاصی نداشته باشد،
        // نقش و ارتباط با پزشک برای مشاهده کافی است.
        if (empty($menuPermissions)) {
            return true;
        }

        // مجوزهای منشی برای همین پزشک
        $userPermissions = $doctor->pivot->permissions ?? [];

        if (is_string($userPermissions)) {
            $userPermissions = json_decode($userPermissions, true) ?? [];
        }

        if (!is_array($userPermissions)) {
            return false;
        }

        // حداقل یکی از مجوزهای موردنیاز منو باید وجود داشته باشد.
        return !empty(array_intersect(
            $menuPermissions,
            $userPermissions
        ));
    }
    public function getFullUrlAttribute(): string
    {
        $rawUrl = $this->attributes['url'] ?? null;
        $routeName = $this->attributes['route_name'] ?? null;

        if ($rawUrl) {
            return $rawUrl;
        }

        if ($routeName) {
            try {
                return route($routeName);
            } catch (\Throwable $e) {
                return '#';
            }
        }

        return '#';
    }
}