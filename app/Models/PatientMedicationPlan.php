<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PatientMedicationPlan extends Model
{
    protected $fillable = ['patient_id', 'user_id', 'prescription_id', 'start_date', 'end_date', 'reminder_times', 'timezone', 'status'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'reminder_times' => 'array'];
    /**
     * Include only plans whose prescription belongs to an encounter in one of
     * the doctor's currently accessible clinics and matches the same patient.
     */
    public function scopeForAccessibleClinics(Builder $query, int $doctorId, array $clinicIds): Builder
    {
        $clinicIds = array_values(array_unique(array_filter(
            array_map('intval', $clinicIds),
            static fn (int $id): bool => $id > 0
        )));

        if ($clinicIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('prescription.encounter', function (Builder $encounter) use ($doctorId, $clinicIds): void {
            $encounter
                ->where('doctor_id', $doctorId)
                ->whereIn('clinic_id', $clinicIds)
                ->whereColumn('patient_encounters.patient_id', 'patient_medication_plans.patient_id');
        });
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function prescription(): BelongsTo { return $this->belongsTo(PatientEncounterPrescription::class, 'prescription_id'); }
    public function doses(): HasMany { return $this->hasMany(PatientMedicationDose::class, 'plan_id')->orderBy('scheduled_for'); }
}
