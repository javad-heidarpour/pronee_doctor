<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicInventoryItem extends Model
{
    protected $fillable = [
        'organization_id', 'clinic_id', 'sku', 'name', 'unit',
        'minimum_quantity', 'current_quantity', 'average_unit_cost', 'is_active',
        'lead_time_days', 'safety_stock_days', 'preferred_supplier',
    ];

    protected $casts = [
        'minimum_quantity' => 'decimal:3',
        'current_quantity' => 'decimal:3',
        'average_unit_cost' => 'integer',
        'is_active' => 'boolean',
        'lead_time_days' => 'integer',
        'safety_stock_days' => 'integer',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'clinic_id');
    }

    public function serviceRequirements(): HasMany
    {
        return $this->hasMany(ClinicServiceInventoryRequirement::class, 'inventory_item_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ClinicInventoryMovement::class, 'inventory_item_id');
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_quantity', '<=', 'minimum_quantity');
    }
}
