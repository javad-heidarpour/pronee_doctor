<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PatientCarePathway extends Model
{
    protected $fillable = ['doctor_id','clinic_id','patient_id','created_by','approved_by','title','description','goal','status','starts_at','ends_at','activated_at','completed_at'];
    protected $casts = ['starts_at'=>'date','ends_at'=>'date','activated_at'=>'datetime','completed_at'=>'datetime'];
    public function doctor(): BelongsTo { return $this->belongsTo(Doctor::class); }
    public function clinic(): BelongsTo { return $this->belongsTo(Address::class, 'clinic_id'); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function steps(): HasMany { return $this->hasMany(PatientCarePathwayStep::class, 'pathway_id')->orderBy('sort_order'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
