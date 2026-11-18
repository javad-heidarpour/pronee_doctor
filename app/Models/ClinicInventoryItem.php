<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicInventoryItem extends Model
{
    protected $fillable = ['organization_id','clinic_id','sku','name','unit','minimum_quantity','current_quantity','average_unit_cost','is_active'];
    protected $casts = ['minimum_quantity' => 'decimal:3','current_quantity' => 'decimal:3','average_unit_cost' => 'integer','is_active' => 'boolean'];
    public function clinic(): BelongsTo { return $this->belongsTo(Address::class, 'clinic_id'); }
    public function movements(): HasMany { return $this->hasMany(ClinicInventoryMovement::class, 'inventory_item_id'); }
    public function scopeLowStock($query) { return $query->whereColumn('current_quantity', '<=', 'minimum_quantity'); }
}