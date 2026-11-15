<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Doctor;

class Fee extends Model
{
    protected $fillable = [
        'title',
        'fee_rial',
        'is_active',
        'created_by',
        'deactivated_at',
    ];

    protected function casts(): array
    {
        return [
            'fee_rial' => 'integer',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'created_by');
    }
}
