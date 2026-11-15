<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Morilog\Jalali\Jalalian;

class Review extends Model
{
    protected $fillable = [

        'doctor_id',

        'user_id',

        'parent_id',

        'patient_name',

        'rating',

        'comment',

        'approved_by',

        'doctor_reply',

        'doctor_reply_at',

        'is_anonymous',

        'is_approved',

        'approved_at',

    ];


    protected $casts = [

        'rating' => 'integer',

        'is_anonymous' => 'boolean',

        'is_approved' => 'boolean',

        'approved_at' => 'datetime',

        'doctor_reply_at' => 'datetime',

    ];


    protected $appends = [

        'patient_display_name',

        'persian_date',

        'stars',

        'has_reply',

    ];



    public function doctor(): BelongsTo
    {
        return $this->belongsTo(
            Doctor::class
        );
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }


    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }



    public function scopeApproved(
        Builder $query
    ): Builder {

        return $query->where(
            'is_approved',
            true
        );

    }


    public function scopePending(
        Builder $query
    ): Builder {

        return $query->where(
            'is_approved',
            false
        );

    }


    public function scopeLatestFirst(
        Builder $query
    ): Builder {

        return $query->latest();

    }



    public function getPatientDisplayNameAttribute(): string
    {

        if ($this->is_anonymous) {

            return 'بیمار ناشناس';

        }


        if ($this->user) {

            return trim(
                $this->user->name .
                ' ' .
                $this->user->family
            );

        }


        return $this->patient_name ?: 'بیمار';

    }



    public function getPersianDateAttribute(): string
    {

        if (!$this->created_at) {

            return '';

        }


        return Jalalian::fromDateTime(
            $this->created_at
        )->format('d F Y');

    }



    public function getStarsAttribute(): string
    {

        return str_repeat(
            '⭐',
            (int) $this->rating
        );

    }



    public function getHasReplyAttribute(): bool
    {

        return !empty(
            $this->doctor_reply
        );

    }



    public function approve(): void
    {

        $this->update([

            'is_approved' => true,

            'approved_at' => now(),

            'approved_by' => auth()->id(),

        ]);

    }



    public function reject(): void
    {

        $this->update([

            'is_approved' => false,

            'approved_at' => null,

            'approved_by' => null,

        ]);

    }



    public function reply(
        string $reply
    ): void {

        $this->update([

            'doctor_reply' => $reply,

            'doctor_reply_at' => now(),

        ]);

    }
}