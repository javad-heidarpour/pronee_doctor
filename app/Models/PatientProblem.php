<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PatientProblem extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_INACTIVE = 'inactive';

    public const CATEGORIES = ['diagnosis', 'symptom', 'condition', 'risk_factor'];
    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_RESOLVED, self::STATUS_INACTIVE];
    public const SEVERITIES = ['mild', 'moderate', 'severe', 'critical'];
    public const CODE_SYSTEMS = ['icd10', 'snomed_ct', 'local'];
    public const SOURCES = ['clinician_assessed', 'patient_reported'];

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'encounter_id',
        'created_by',
        'updated_by',
        'category',
        'title',
        'code',
        'code_system',
        'description',
        'status',
        'severity',
        'onset_date',
        'resolved_on',
        'source',
    ];

    protected $casts = [
        'onset_date' => 'date:Y-m-d',
        'resolved_on' => 'date:Y-m-d',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(PatientEncounter::class, 'encounter_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}