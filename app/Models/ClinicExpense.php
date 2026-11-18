<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicExpense extends Model
{
    protected static function booted(): void
    {
        static::updating(fn () => throw ValidationException::withMessages(['record' => 'سابقه مالی ثبت‌شده قابل ویرایش نیست.']));
        static::deleting(fn () => throw ValidationException::withMessages(['record' => 'سابقه مالی ثبت‌شده قابل حذف نیست.']));
    }

    protected $fillable = ['doctor_id', 'clinic_id', 'recorded_by', 'category', 'description', 'amount', 'expense_date', 'payment_method', 'reference', 'notes'];
    protected $casts = ['amount' => 'integer', 'expense_date' => 'date'];
    public function clinic(): BelongsTo { return $this->belongsTo(Address::class, 'clinic_id'); }
}