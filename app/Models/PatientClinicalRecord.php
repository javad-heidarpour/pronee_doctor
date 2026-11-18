<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientClinicalRecord extends Model
{
    use SoftDeletes;

    public const KIND_ALLERGY = 'allergy';
    public const KIND_MEDICATION = 'medication';
    public const KIND_HISTORY = 'medical_history';
    public const KIND_ADVERSE_REACTION = 'adverse_reaction';

    public const KINDS = [
        self::KIND_ALLERGY,
        self::KIND_MEDICATION,
        self::KIND_HISTORY,
        self::KIND_ADVERSE_REACTION,
    ];

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'kind',
        'title',
        'description',
        'dosage',
        'frequency',
        'start_date',
        'end_date',
        'is_active',
        'related_record_id',
        'related_allergy_record_id',
        'reaction_severity',
        'reaction_date',
        'action_taken',
        'reaction_outcome',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'reaction_date' => 'date:Y-m-d',
        'is_active' => 'boolean',
    ];

    public function relatedAllergyRecord(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_allergy_record_id');
    }

    public function relatedRecord(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_record_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
