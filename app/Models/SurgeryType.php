<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurgeryType extends Model
{
    protected $fillable = [
        'title',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'surgery_type_id');
    }
}