<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [

        'post_id',

        'name',

        'email',

        'comment',

        'rating',

        'is_approved',

        'ip',

        'user_agent',

    ];

    protected $casts = [

        'is_approved' => 'boolean',

        'rating' => 'integer',

    ];

    /**
     * مقاله
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * فقط نظرات تایید شده
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * آخرین نظرات
     */
    public function scopeLatestFirst($query)
    {
        return $query->latest();
    }
}