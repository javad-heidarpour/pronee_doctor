<?php

// app/Models/DoctorImage.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DoctorImage extends Model
{
    protected $fillable = [
        'doctor_id',
        'image_url',
        'caption',
        'category',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // دسته‌بندی‌های موجود
    const CATEGORIES = [
        'Heartfelt' => 'دلنوشته',
        'Portfolio' => 'نمونه کار',
        'Satisfaction' => 'رضایت',
        'Workplace' => 'محیط مطب',
        'equipment' => 'تجهیزات پزشکی',
        'team' => 'تیم پزشکی',
        'certificate' => 'گواهینامه‌ها',
        'other' => 'سایر',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    // Scope برای تصاویر فعال
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope برای مرتب‌سازی
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    // Scope بر اساس دسته‌بندی
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // نام فارسی دسته‌بندی
    public function getCategoryNameAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? 'سایر';
    }

    // URL کامل تصویر
    public function getFullImageUrlAttribute(): string
    {
        if (str_starts_with($this->image_url, 'http')) {
            return $this->image_url;
        }
        return Storage::url($this->image_url);
    }
}