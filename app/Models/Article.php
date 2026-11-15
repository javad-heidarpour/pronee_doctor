<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // ✅ این خط باید وجود داشته باشد


// Article.php
class Article extends Model
{
    protected $casts = [
        'schema_markup' => 'array',
        'published_at' => 'datetime',
        'is_pinned' => 'boolean'
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    } // استفاده از slug در URL به جای id

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
    public function category()
    {
        return $this->belongsTo(ArticleCategory::class);
    }
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    // Scope برای مقالات منتشر شده
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('published_at', '<=', now());
    }
}