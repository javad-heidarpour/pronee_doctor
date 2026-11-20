<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ClinicSharedService extends Model
{
    protected $fillable = [
        'organization_id','doctor_id','clinic_id','created_by_user_id','title','category',
        'description','price_rial','weekly_capacity','status','available_from','available_until',
    ];

    protected function casts(): array
    {
        return ['price_rial'=>'integer','weekly_capacity'=>'integer','available_from'=>'date','available_until'=>'date'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(ClinicOrganization::class, 'organization_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'clinic_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ClinicSharedServiceRequest::class, 'shared_service_id');
    }
}
