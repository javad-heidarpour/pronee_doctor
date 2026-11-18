<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientFollowUpTemplate extends Model
{
    protected $fillable = [
        'doctor_id', 'title', 'category', 'description',
        'default_days', 'contact_method', 'send_sms', 'is_active',
    ];

    protected $casts = [
        'default_days' => 'integer',
        'send_sms' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(PatientFollowUpTask::class, 'template_id');
    }
}