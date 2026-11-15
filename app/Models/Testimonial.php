<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'name',
        'comment',
        'service',
        'rating',
        'is_approved',
        'sort_order',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }
}