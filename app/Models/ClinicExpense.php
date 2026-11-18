<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicExpense extends Model
{
    protected $fillable = ['doctor_id', 'clinic_id', 'recorded_by', 'category', 'description', 'amount', 'expense_date', 'payment_method', 'reference', 'notes'];
    protected $casts = ['amount' => 'integer', 'expense_date' => 'date'];
    public function clinic(): BelongsTo { return $this->belongsTo(Address::class, 'clinic_id'); }
}