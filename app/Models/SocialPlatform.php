<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // ✅ این خط باید وجود داشته باشد

use App\Models\DoctorSocial;

class SocialPlatform extends Model
{
    protected $fillable=[
        'name',
        'title',
        'icon',
        'color',
        'base_url',
        'sort_order',
        'is_active'
    ];

    public function doctors()
    {
        return $this->hasMany(DoctorSocial::class);
    }
}