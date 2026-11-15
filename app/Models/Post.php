<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;

class Post extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'posts';

    protected $fillable = [
        'category_id',
        'doctor_id',
        'author_id',

        'title',
        'slug',
        'excerpt',
        'content',

        'featured_image',

        'meta_title',
        'meta_description',
        'canonical_url',

        'keywords',

        'views_count',
        'reading_time',

        'published_at',

        'is_featured',
        'is_pinned',
        'is_published',
    ];

    protected $casts = [
        'keywords' => 'array',
        'published_at' => 'datetime',

        'views_count' => 'integer',
        'reading_time' => 'integer',

        'is_featured' => 'boolean',
        'is_pinned' => 'boolean',
        'is_published' => 'boolean',
    ];

    protected $attributes = [
        'views_count' => 0,
        'reading_time' => 5,

        'is_featured' => false,
        'is_pinned' => false,
        'is_published' => false,
    ];

    protected $appends = [
        'featured_image_url',
        'persian_published_at',
        'average_rating',
        'comments_count',
    ];
    /*
|--------------------------------------------------------------------------
| Boot
|--------------------------------------------------------------------------
*/



    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withDefault();
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class)->withDefault();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id')->withDefault();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            'post_tag',
            'post_id',
            'tag_id'
        );
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->comments()
            ->approved()
            ->root()
            ->latestFirst();
    }
    /*
|--------------------------------------------------------------------------
| Query Scopes
|--------------------------------------------------------------------------
*/

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeUnpublished(Builder $query): Builder
    {
        return $query->where('is_published', false);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    public function scopeLatestPublished(Builder $query): Builder
    {
        return $query->published()->latest('published_at');
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->latest('published_at');
    }

    public function scopeByDoctor(
        Builder $query,
        int $doctorId
    ): Builder {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByAuthor(
        Builder $query,
        int $authorId
    ): Builder {
        return $query->where('author_id', $authorId);
    }

    public function scopeByCategory(
        Builder $query,
        int $categoryId
    ): Builder {
        return $query->where('category_id', $categoryId);
    }

    public function scopeExclude(
        Builder $query,
        int $postId
    ): Builder {
        return $query->whereKeyNot($postId);
    }

    public function scopeSearch(
        Builder $query,
        ?string $search
    ): Builder {

        if (blank($search)) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($search) {

            $builder
                ->where('title', 'like', "%{$search}%")
                ->orWhere('excerpt', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
                ->orWhere('meta_title', 'like', "%{$search}%")
                ->orWhere('meta_description', 'like', "%{$search}%");

        });
    }
    /*
|--------------------------------------------------------------------------
| Accessors
|--------------------------------------------------------------------------
*/

    protected function featuredImageUrl(): Attribute
    {
        return Attribute::make(

            get: function (): ?string {

                if (blank($this->featured_image)) {
                    return null;
                }

                if (
                    str_starts_with($this->featured_image, 'http') ||
                    str_starts_with($this->featured_image, '/')
                ) {
                    return $this->featured_image;
                }

                return asset('storage/' . ltrim($this->featured_image, '/'));
            }

        );
    }

    protected function persianPublishedAt(): Attribute
    {
        return Attribute::make(

            get: function (): string {

                if (!$this->published_at) {
                    return '';
                }

                return Jalalian::fromDateTime(
                    $this->published_at
                )->format('d F Y - H:i');
            }

        );
    }

    protected function averageRating(): Attribute
    {
        return Attribute::make(

            get: function (): float {

                if ($this->relationLoaded('approvedComments')) {

                    return round(
                        $this->approvedComments
                            ->whereNotNull('rating')
                            ->avg('rating') ?? 0,
                        1
                    );
                }

                return round(
                    (float) $this->approvedComments()
                        ->whereNotNull('rating')
                        ->avg('rating'),
                    1
                );
            }

        );
    }

    protected function commentsCount(): Attribute
    {
        return Attribute::make(

            get: function (): int {

                if ($this->relationLoaded('approvedComments')) {
                    return $this->approvedComments->count();
                }

                return $this->approvedComments()->count();
            }

        );
    }

    protected function fullUrl(): Attribute
    {
        return Attribute::make(
            get: fn(): string => route('blog.show', $this->slug)
        );
    }
    /*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/





    public function isPublished(): bool
    {
        return $this->is_published
            && !is_null($this->published_at)
            && $this->published_at->lte(now());
    }

    public function canReceiveComments(): bool
    {
        return $this->isPublished();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }


    public function incrementViewsCount(): self
    {
        $this->increment('views_count');

        $this->refresh();

        return $this;
    }

    public function attachTags(array $tags = []): void
    {
        $this->tags()->syncWithoutDetaching($tags);
    }

    public function detachTags(): void
    {
        $this->tags()->detach();
    }

    public function scopeWithBlogRelations(Builder $query): Builder
    {
        return $query->with([
            'doctor',
            'author',
            'category',
            'tags',
        ]);
    }

    public function scopeWithCommentStatistics(Builder $query): Builder
    {
        return $query
            ->withCount([
                'approvedComments as comments_count',
            ])
            ->withAvg([
                'approvedComments as average_rating',
            ], 'rating');
    }
}