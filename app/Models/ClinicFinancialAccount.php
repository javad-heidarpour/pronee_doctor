<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicFinancialAccount extends Model
{
    protected $fillable = ['organization_id','clinic_id','code','name','type','is_system','is_active'];
    protected $casts = ['is_system' => 'boolean','is_active' => 'boolean'];
    public function lines(): HasMany { return $this->hasMany(ClinicJournalLine::class, 'account_id'); }
}