<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicJournalLine extends Model
{
    protected static function booted(): void
    {
        static::updating(fn () => throw ValidationException::withMessages(['finance' => 'ردیف سند حسابداری قابل ویرایش نیست.']));
        static::deleting(fn () => throw ValidationException::withMessages(['finance' => 'ردیف سند حسابداری قابل حذف نیست.']));
    }

    protected $fillable = ['journal_entry_id','account_id','debit','credit','memo'];
    protected $casts = ['debit' => 'integer','credit' => 'integer'];
    public function entry(): BelongsTo { return $this->belongsTo(ClinicJournalEntry::class, 'journal_entry_id'); }
    public function account(): BelongsTo { return $this->belongsTo(ClinicFinancialAccount::class, 'account_id'); }
}