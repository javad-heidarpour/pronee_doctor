<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PatientHealthObservation extends Model
{
    public const MEASUREMENTS = [
        'weight' => ['unit' => 'kg', 'min' => 20, 'max' => 350, 'label' => 'وزن'],
        'systolic_bp' => ['unit' => 'mmHg', 'min' => 50, 'max' => 280, 'label' => 'فشار خون سیستولیک'],
        'diastolic_bp' => ['unit' => 'mmHg', 'min' => 30, 'max' => 180, 'label' => 'فشار خون دیاستولیک'],
        'blood_glucose' => ['unit' => 'mg/dL', 'min' => 20, 'max' => 700, 'label' => 'قند خون'],
        'pulse' => ['unit' => 'bpm', 'min' => 25, 'max' => 240, 'label' => 'ضربان قلب'],
        'temperature' => ['unit' => '°C', 'min' => 30, 'max' => 45, 'label' => 'دمای بدن'],
        'spo2' => ['unit' => '%', 'min' => 50, 'max' => 100, 'label' => 'اکسیژن خون'],
    ];

    protected $fillable = [
        'patient_id', 'user_id', 'measurement_type', 'value', 'unit', 'observed_at', 'note',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'observed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
