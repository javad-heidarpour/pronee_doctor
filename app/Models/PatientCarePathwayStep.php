<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PatientCarePathwayStep extends Model
{
    protected $fillable = ['pathway_id','title','description','category','due_at','assigned_to','sort_order','status','patient_reported_at','verified_at','verified_by','verification_note'];
    protected $casts = ['due_at'=>'datetime','patient_reported_at'=>'datetime','verified_at'=>'datetime'];
    public function pathway(): BelongsTo { return $this->belongsTo(PatientCarePathway::class, 'pathway_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
}
