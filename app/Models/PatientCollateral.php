<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientCollateral extends Model
{
    protected $fillable = ['treatment_plan_id', 'received_by', 'type', 'identifier', 'face_value', 'due_date', 'status', 'received_at', 'returned_at', 'collected_at', 'notes', 'reminder_enabled', 'last_reminded_at'];
    protected $casts = ['face_value' => 'integer', 'due_date' => 'date', 'received_at' => 'datetime', 'returned_at' => 'datetime', 'collected_at' => 'datetime', 'reminder_enabled' => 'boolean', 'last_reminded_at' => 'datetime'];
    public function plan(): BelongsTo { return $this->belongsTo(PatientTreatmentPlan::class, 'treatment_plan_id'); }
}