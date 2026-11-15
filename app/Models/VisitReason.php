<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitReason extends Model
{
    protected $fillable = [
        'title',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'visit_reason_id');
    }
}
