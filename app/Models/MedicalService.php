<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalService extends Model
{
    use SoftDeletes;


    protected $guarded = ['id'];

    protected $casts = [
        'faq_json' => 'array',
        'schema_json' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];
}
