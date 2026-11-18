<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PatientMedicationDose extends Model
{
    protected $fillable = ['plan_id', 'scheduled_for', 'status', 'recorded_by_user_id', 'recorded_at', 'note'];
    protected $casts = ['scheduled_for' => 'datetime', 'recorded_at' => 'datetime'];
    public function plan(): BelongsTo { return $this->belongsTo(PatientMedicationPlan::class, 'plan_id'); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by_user_id'); }
}
