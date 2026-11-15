<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'phone',
        'mobile',
        'email',
        'address',
        'city',
        'country',
        'working_hours',
        'social_links',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'social_links' => 'array',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}