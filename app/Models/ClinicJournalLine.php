<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicJournalLine extends Model
{
    protected $fillable = ['journal_entry_id','account_id','debit','credit','memo'];
    protected $casts = ['debit' => 'integer','credit' => 'integer'];
    public function entry(): BelongsTo { return $this->belongsTo(ClinicJournalEntry::class, 'journal_entry_id'); }
    public function account(): BelongsTo { return $this->belongsTo(ClinicFinancialAccount::class, 'account_id'); }
}