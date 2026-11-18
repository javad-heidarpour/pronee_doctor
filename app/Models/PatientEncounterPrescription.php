<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PatientEncounterPrescription extends Model
{
    protected $fillable = [
        'encounter_id', 'medication_name', 'strength', 'dosage', 'route',
        'frequency', 'duration_days', 'quantity', 'instructions', 'sort_order',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(PatientEncounter::class, 'encounter_id');
    }
}