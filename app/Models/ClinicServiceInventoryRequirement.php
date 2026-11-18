<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicServiceInventoryRequirement extends Model
{
    protected $fillable = ['service_id', 'inventory_item_id', 'quantity'];
    protected $casts = ['quantity' => 'decimal:3'];
    public function service(): BelongsTo { return $this->belongsTo(ClinicService::class, 'service_id'); }
    public function inventoryItem(): BelongsTo { return $this->belongsTo(ClinicInventoryItem::class, 'inventory_item_id'); }
}
