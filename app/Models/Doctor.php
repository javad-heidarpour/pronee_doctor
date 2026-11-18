<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Doctor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'city_id',
        'specialty_id',
        'caption',
        'name',
        'family',
        'slug',
        'email',
        'password',
        'phone',
        'clinic_address',
        'photo',
        'Medical_system_number',
        'banner',
        'description',
        'meta_title',
        'meta_description',
        'specialty_title',
        'years_of_experience',
        'satisfaction_rate',
        'is_active',
        'registration_status',
        'site_expires_at',
        'trial_started_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
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

    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = Hash::make($value);
    }

    protected static function booted(): void
    {
        static::creating(function ($doctor) {
            if (empty($doctor->slug)) {
                $doctor->slug = Str::slug($doctor->name);
            }
        });
    }

    // Relationships

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

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

    public function clinicOrganizations(): BelongsToMany
    {
        return $this->belongsToMany(ClinicOrganization::class, 'clinic_organization_doctors', 'doctor_id', 'organization_id')->withPivot(['role', 'revenue_share_basis_points', 'status', 'joined_at'])->withTimestamps();
    }

    public function treatmentItems(): HasMany
    {
        return $this->hasMany(PatientTreatmentItem::class, 'provider_doctor_id');
    }

    public function address(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(DoctorWorkingHour::class);
    }

    public function activeWorkingHours(): HasMany
    {
        return $this->workingHours()->where('is_active', true);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function secretaries(): BelongsToMany
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
            ->withPivot('permissions', 'clinic_ids')
            ->withTimestamps();
    }

    public function socials(): HasMany
    {
        return $this->hasMany(DoctorSocial::class);
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

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'doctor_id');
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    public function images(): HasMany
    {
        return $this->hasMany(DoctorImage::class);
    }

    public function activeImages(): HasMany
    {
        return $this->hasMany(DoctorImage::class)->where('is_active', true);
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

    public function getReviewsCountAttribute(): int
    {
        return $this->approvedReviews()->count();
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->approvedReviews()->avg('rating') ?? 0, 1);
    }
}