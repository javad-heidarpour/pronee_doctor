<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientFollowUpTask extends Model
{
    protected $fillable = [
        'doctor_id', 'clinic_id', 'patient_id', 'appointment_id', 'treatment_plan_id', 'collateral_id', 'template_id',
        'assigned_to', 'created_by', 'plan_uuid', 'title', 'description',
        'due_at', 'priority', 'contact_method', 'send_sms', 'sms_consent_confirmed', 'status', 'outcome',
        'result_notes', 'completed_at', 'sms_sent_at', 'sms_attempted_at',
        'sms_attempts', 'last_sms_error',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'sms_sent_at' => 'datetime',
        'sms_attempted_at' => 'datetime',
        'sms_attempts' => 'integer',
        'send_sms' => 'boolean',
        'sms_consent_confirmed' => 'boolean',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'clinic_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function collateral(): BelongsTo { return $this->belongsTo(PatientCollateral::class, 'collateral_id'); }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(PatientTreatmentPlan::class, 'treatment_plan_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PatientFollowUpTemplate::class, 'template_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Restrict follow-up records to a doctor's clinics.
     *
     * Unassigned legacy tasks are only visible to doctor/admin accounts because
     * they cannot be safely attributed to a secretary's clinic assignment.
     */
    public function scopeForAccessibleClinics(
        Builder $query,
        int $doctorId,
        array $clinicIds,
        bool $includeLegacyUnassigned = false
    ): Builder {
        $clinicIds = array_values(array_unique(array_filter(
            array_map('intval', $clinicIds),
            static fn (int $id): bool => $id > 0
        )));

        $query->where('doctor_id', $doctorId);

        if ($clinicIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $clinicQuery) use ($clinicIds, $includeLegacyUnassigned): void {
            $clinicQuery->whereIn('clinic_id', $clinicIds);

            if ($includeLegacyUnassigned) {
                $clinicQuery->orWhereNull('clinic_id');
            }
        });
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')->where('due_at', '<', now());
    }
}