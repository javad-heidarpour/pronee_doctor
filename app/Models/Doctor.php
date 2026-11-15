<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // ✅ این خط باید وجود داشته باشد
use Illuminate\Support\Facades\Hash;
use App\Models\City;
use App\Models\Specialty;
use App\Models\Address;
use App\Models\TimeSlot;

// class Doctor extends Model
class Doctor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'caption',
        'name',
        'family',
        'slug',
        'photo',
        'description',
        'meta_title',
        'meta_description',
        'specialty_title',
        'years_of_experience',
        'satisfaction_rate',
        'is_active',
        'city_id',
        'clinic_address',
        'Medical_system_number',
        'specialty_id',
        'email',
        'password',
        'phone',
        'role',
        'user_id',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'satisfaction_rate' => 'decimal:2',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function socials()
    {
        return $this->hasMany(DoctorSocial::class);
    }
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(DoctorWorkingHour::class);
    }

    public function activeWorkingHours(): HasMany
    {
        return $this->workingHours()->where('is_active', true);
    }
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    protected static function booted(): void
    {
        static::creating(function ($doctor) {
            if (empty($doctor->slug)) {
                $doctor->slug = Str::slug($doctor->name);
            }
        });
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }
    // Relationships
    /**
     * منشی‌های متصل به این پزشک
     */
    public function secretaries()
    {
        return $this->belongsToMany(
            User::class,
            'doctor_user',
            'doctor_id',
            'user_id',
            'id',
            'id'
        )
            ->where('role', 'secretary')
            ->withPivot('permissions')
            ->withTimestamps();
    }
    public function address(): HasMany
    {
        return $this->hasMany(Address::class);
    }
    // use Illuminate\Database\Eloquent\Relations\HasMany;

 public function specialty(): BelongsTo
{
    return $this->belongsTo(Specialty::class, 'specialty_id')
        ->where('is_active', true);
}
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function contact(): HasOne
    {
        return $this->hasOne(Contact::class);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class)->orderBy('sort_order');
    }

    public function approvedTestimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class)
            ->where('is_approved', true)
            ->orderBy('sort_order');
    }




    public function honorBadges(): HasMany
    {
        return $this->hasMany(HonorBadge::class)->orderBy('sort_order');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    // Accessors
    public function getInitialAttribute(): string
    {
        return mb_substr($this->name, 0, 1);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }



    /*
    |--------------------------------------------------------------------------
    | Reviews
    |--------------------------------------------------------------------------
    */

    public function reviews(): HasMany
    {
        return $this->hasMany(
            Review::class,
            'doctor_id'
        );
    }


    public function approvedReviews(): HasMany
    {
        return $this->reviews()
            ->where('is_approved', true);
    }


    /*
    |--------------------------------------------------------------------------
    | Review Statistics
    |--------------------------------------------------------------------------
    */


    public function getReviewsCountAttribute(): int
    {
        return $this->approvedReviews()->count();
    }



    public function getAverageRatingAttribute(): float
    {
        return round(
            $this->approvedReviews()
                ->avg('rating') ?? 0,
            1
        );
    }





    // app/Models/Doctor.php
    public function images(): HasMany
    {
        return $this->hasMany(DoctorImage::class);
    }

    public function activeImages()
    {
        return $this->hasMany(DoctorImage::class)
            ->where('is_active', true);
    }
    /**
     * مقالات پزشک
     */

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class)->orderByDesc('published_at');
    }
}
