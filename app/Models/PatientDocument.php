<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientDocument extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'clinic_id',
        'appointment_id',
        'uploaded_by_user_id',
        'archived_by_user_id',
        'category',
        'title',
        'description',
        'original_name',
        'disk',
        'path',
        'mime_type',
        'file_size',
        'checksum_sha256',
        'uploaded_at',
        'archived_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'uploaded_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    protected $hidden = [
        'path',
        'disk',
        'checksum_sha256',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'clinic_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by_user_id');
    }
}
