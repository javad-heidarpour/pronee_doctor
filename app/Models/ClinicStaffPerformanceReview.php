<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ClinicStaffPerformanceReview extends Model
{
    protected $fillable = [
        'organization_id','clinic_id','staff_user_id','reviewer_user_id',
        'period_start','period_end','reliability_score','task_completion_score',
        'teamwork_score','communication_score','learning_score','strengths',
        'improvement_areas','goals','reviewer_notes','status','published_at',
        'acknowledged_at','employee_response',
    ];

    protected function casts(): array
    {
        return [
            'period_start'=>'date',
            'period_end'=>'date',
            'reliability_score'=>'integer',
            'task_completion_score'=>'integer',
            'teamwork_score'=>'integer',
            'communication_score'=>'integer',
            'learning_score'=>'integer',
            'published_at'=>'datetime',
            'acknowledged_at'=>'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class,'staff_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class,'reviewer_user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(ClinicOrganization::class,'organization_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Address::class,'clinic_id');
    }
}
