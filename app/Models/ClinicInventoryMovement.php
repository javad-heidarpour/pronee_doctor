<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicInventoryMovement extends Model
{
    protected static function booted(): void
    {
        static::updating(fn () => throw ValidationException::withMessages(['record' => 'سابقه مالی ثبت‌شده قابل ویرایش نیست.']));
        static::deleting(fn () => throw ValidationException::withMessages(['record' => 'سابقه مالی ثبت‌شده قابل حذف نیست.']));
    }

    protected $fillable = ['inventory_item_id','recorded_by','movement_type','quantity','unit_cost','reference','notes','occurred_at'];
    protected $casts = ['quantity' => 'decimal:3','unit_cost' => 'integer','occurred_at' => 'datetime'];
    public function item(): BelongsTo { return $this->belongsTo(ClinicInventoryItem::class, 'inventory_item_id'); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}