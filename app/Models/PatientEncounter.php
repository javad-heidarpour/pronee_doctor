<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

final class PatientEncounter extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SIGNED = 'signed';

    protected static function booted(): void
    {
        static::updating(function (self $encounter): void {
            if ($encounter->getOriginal('status') === self::STATUS_SIGNED) {
                throw ValidationException::withMessages([
                    'encounter' => 'یادداشت نهایی‌شده غیرقابل‌ویرایش است؛ برای اصلاح، الحاقیه جدید ثبت کنید.',
                ]);
            }
        });
    }

    protected $fillable = [
        'doctor_id', 'patient_id', 'appointment_id', 'amends_encounter_id', 'clinic_id', 'author_id', 'signed_by',
        'note_type', 'status', 'subjective', 'objective', 'assessment', 'plan',
        'diagnosis', 'diagnosis_code', 'reviewed_allergies', 'reviewed_medications',
        'reviewed_vitals', 'clinical_snapshot', 'signed_at',
    ];

    protected $casts = [
        'reviewed_allergies' => 'boolean',
        'reviewed_medications' => 'boolean',
        'reviewed_vitals' => 'boolean',
        'clinical_snapshot' => 'array',
        'signed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'clinic_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function amendedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'amends_encounter_id');
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(self::class, 'amends_encounter_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(PatientEncounterPrescription::class, 'encounter_id')->orderBy('sort_order');
    }
}