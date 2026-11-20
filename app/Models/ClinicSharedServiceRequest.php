<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ClinicSharedServiceRequest extends Model
{
    protected $fillable = [
        'shared_service_id','requesting_organization_id','requesting_doctor_id','requesting_clinic_id',
        'requested_by_user_id','responded_by_user_id','requested_capacity','status',
        'request_note','response_note','responded_at',
    ];

    protected function casts(): array
    {
        return ['requested_capacity'=>'integer','responded_at'=>'datetime'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ClinicSharedService::class, 'shared_service_id');
    }

    public function requestingOrganization(): BelongsTo
    {
        return $this->belongsTo(ClinicOrganization::class, 'requesting_organization_id');
    }
}
