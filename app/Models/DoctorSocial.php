<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // ✅ این خط باید وجود داشته باشد

use App\Models\SocialPlatform;

class DoctorSocial extends Model
{
    protected $fillable=[
        'doctor_id',
        'social_platform_id',
        'username',
        'url',
        'is_visible'
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function platform()
    {
        return $this->belongsTo(SocialPlatform::class,'social_platform_id');
    }
}