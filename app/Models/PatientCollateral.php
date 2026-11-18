<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientCollateral extends Model
{
    protected $fillable = ['treatment_plan_id', 'received_by', 'type', 'identifier', 'face_value', 'due_date', 'status', 'received_at', 'returned_at', 'notes'];
    protected $casts = ['face_value' => 'integer', 'due_date' => 'date', 'received_at' => 'datetime', 'returned_at' => 'datetime'];
    public function plan(): BelongsTo { return $this->belongsTo(PatientTreatmentPlan::class, 'treatment_plan_id'); }
}