<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Morilog\Jalali\Jalalian;

class PostComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',

        'name',
        'email',

        'comment',
        'rating',

        'is_anonymous',
        'is_approved',

        'approved_at',
        'approved_by',

        'ip_address',
        'user_agent',

        'likes_count',
        'dislikes_count',

        'is_edited',
        'edited_at',
    ];

    protected $casts = [
        'rating'          => 'integer',

        'is_anonymous'    => 'boolean',
        'is_approved'     => 'boolean',
        'is_edited'       => 'boolean',

        'likes_count'     => 'integer',
        'dislikes_count'  => 'integer',

        'approved_at'     => 'datetime',
        'edited_at'       => 'datetime',
    ];

    protected $appends = [
        'display_name',
        'persian_date',
        'stars',
        'has_replies',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * مقاله
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * کاربر
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * نظر والد
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(PostComment::class, 'parent_id');
    }

    /**
     * پاسخ‌ها
     */
    public function replies(): HasMany
    {
        return $this->hasMany(PostComment::class, 'parent_id')
            ->approved()
            ->oldest();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * فقط نظرات تایید شده
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }
public function scopeRoot($query)
{
    return $query->whereNull('parent_id');
}
    /**
     * فقط نظرات اصلی
     */
    public function scopeParent(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * فقط پاسخ‌ها
     */
    public function scopeReplies(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * جدیدترین
     */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->latest();
    }

    /**
     * قدیمی‌ترین
     */
    public function scopeOldestFirst(Builder $query): Builder
    {
        return $query->oldest();
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * نام نمایشی
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'کاربر ناشناس';
        }

        if ($this->user) {
            return $this->user->name;
        }

        return $this->name ?: 'کاربر مهمان';
    }

    /**
     * تاریخ شمسی
     */
    public function getPersianDateAttribute(): string
    {
        if (!$this->created_at) {
            return '';
        }

        return Jalalian::fromDateTime($this->created_at)
            ->format('d F Y - H:i');
    }

    /**
     * ستاره‌ها
     */
    public function getStarsAttribute(): string
    {
        if (!$this->rating) {
            return '';
        }

        return str_repeat('⭐', $this->rating);
    }

    /**
     * دارای پاسخ؟
     */
    public function getHasRepliesAttribute(): bool
    {
        if ($this->relationLoaded('replies')) {
            return $this->replies->isNotEmpty();
        }

        return $this->replies()->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * آیا نظر اصلی است؟
     */
    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * آیا پاسخ است؟
     */
    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * قابل ویرایش؟
     */
    public function canBeEdited(): bool
    {
        return !$this->is_edited;
    }
}