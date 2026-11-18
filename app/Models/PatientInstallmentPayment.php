<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientInstallmentPayment extends Model
{
    protected static function booted(): void
    {
        static::updating(fn () => throw ValidationException::withMessages(['record' => 'سابقه مالی ثبت‌شده قابل ویرایش نیست.']));
        static::deleting(fn () => throw ValidationException::withMessages(['record' => 'سابقه مالی ثبت‌شده قابل حذف نیست.']));
    }

    protected $fillable = ['installment_id', 'received_by', 'amount', 'method', 'reference', 'paid_at', 'notes'];
    protected $casts = ['amount' => 'integer', 'paid_at' => 'datetime'];
    public function installment(): BelongsTo { return $this->belongsTo(PatientTreatmentInstallment::class, 'installment_id'); }
}