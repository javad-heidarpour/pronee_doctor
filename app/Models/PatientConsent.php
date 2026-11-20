<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

final class PatientConsent extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SIGNED = 'signed';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'clinic_id',
        'appointment_id',
        'created_by_user_id',
        'signed_by_user_id',
        'consent_type',
        'title',
        'consent_text',
        'content_sha256',
        'status',
        'signer_name',
        'signature_method',
        'signature_sha256',
        'signed_at',
        'signer_ip_address',
        'signer_user_agent',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    protected $hidden = [
        'content_sha256',
        'signature_sha256',
        'signer_ip_address',
        'signer_user_agent',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $consent): void {
            if ($consent->getOriginal('status') === self::STATUS_SIGNED) {
                throw ValidationException::withMessages([
                    'consent' => 'رضایت‌نامه امضاشده غیرقابل‌ویرایش است.',
                ]);
            }
        });

        static::deleting(function (): void {
            throw ValidationException::withMessages([
                'consent' => 'سوابق رضایت‌نامه قابل حذف نیستند.',
            ]);
        });
    }

    public function hasValidContentHash(): bool
    {
        return hash_equals(
            (string) $this->content_sha256,
            hash('sha256', (string) $this->consent_text)
        );
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function doctor(): BelongsTo { return $this->belongsTo(Doctor::class); }
    public function clinic(): BelongsTo { return $this->belongsTo(Address::class, 'clinic_id'); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function signer(): BelongsTo { return $this->belongsTo(User::class, 'signed_by_user_id'); }
}
