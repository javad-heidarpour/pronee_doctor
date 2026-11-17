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

    public const KINDS = [
        self::KIND_ALLERGY,
        self::KIND_MEDICATION,
        self::KIND_HISTORY,
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
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'is_active' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
