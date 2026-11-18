<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalReferralEvent extends Model
{
    public $timestamps = false;
    protected $fillable = ['medical_referral_id', 'user_id', 'event', 'from_status', 'to_status', 'description', 'metadata', 'created_at'];
    protected $casts = ['metadata' => 'array', 'created_at' => 'datetime'];
    public function referral(): BelongsTo { return $this->belongsTo(MedicalReferral::class, 'medical_referral_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
