<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class DoctorArticle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'doctor_id', 'title', 'slug', 'excerpt', 'body',
        'status', 'published_at', 'meta_title', 'meta_description',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
