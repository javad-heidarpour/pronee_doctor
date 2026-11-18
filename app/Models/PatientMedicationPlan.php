<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PatientMedicationPlan extends Model
{
    protected $fillable = ['patient_id', 'user_id', 'prescription_id', 'start_date', 'end_date', 'reminder_times', 'timezone', 'status'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'reminder_times' => 'array'];
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function prescription(): BelongsTo { return $this->belongsTo(PatientEncounterPrescription::class, 'prescription_id'); }
    public function doses(): HasMany { return $this->hasMany(PatientMedicationDose::class, 'plan_id')->orderBy('scheduled_for'); }
}
