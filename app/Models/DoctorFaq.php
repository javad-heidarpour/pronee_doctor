<?php

declare(strict_types=1);

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

final class DoctorFaq extends Model
{
    protected $fillable = [
        'doctor_id', 'question', 'answer', 'category',
        'sort_order', 'is_published', 'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
