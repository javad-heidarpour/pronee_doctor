<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicJournalEntry extends Model
{
    protected $fillable = ['organization_id','clinic_id','doctor_id','created_by','source_type','source_id','reference','description','occurred_at','status','idempotency_key'];
    protected $casts = ['occurred_at' => 'datetime'];
    public function lines(): HasMany { return $this->hasMany(ClinicJournalLine::class, 'journal_entry_id'); }
}