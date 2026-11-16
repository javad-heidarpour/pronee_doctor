<?php

namespace App\Actions\Portal\Appointment;

use App\Models\Doctor;
use App\Models\SurgeryType;
use App\Models\VisitReason;
use Illuminate\Support\Collection;

class BuildPatientIndexOptionsAction
{
    public function handle(Doctor $doctor, array $accessibleClinicIds): array
    {
        return [
            'clinics' => $doctor->address()
                ->whereIn('addresses.id', $accessibleClinicIds)
                ->select('addresses.id', 'addresses.name')
                ->get(),
            'visitReasons' => VisitReason::query()
                ->select('id', 'title')
                ->orderBy('title')
                ->get(),
            'surgeryTypes' => SurgeryType::query()
                ->select('id', 'title')
                ->orderBy('title')
                ->get(),
        ];
    }
}
