<?php

namespace App\\Http\\Controllers\\Portal\\Concerns;

use App\\Models\\Doctor;
use App\\Models\\DoctorWorkingHour;
use App\\Models\\TimeSlot;

trait AuthorizesPortalSchedules
{
    protected function authorizeClinic(Doctor $doctor, int $clinicId): void
    {
        abort_unless(
            in_array($clinicId, $this->accessibleClinicIds($doctor), true),
            403,
            'شما به این مطب دسترسی ندارید.'
        );
    }

    protected function authorizeTimeSlot(TimeSlot $slot, Doctor $doctor): void
    {
        abort_unless(
            (int) $slot->doctor_id === (int) $doctor->id &&
            in_array(
                (int) $slot->clinic_id,
                $this->accessibleClinicIds($doctor),
                true
            ),
            403,
            'دسترسی غیرمجاز'
        );
    }

    protected function authorizeWorkingHour(
        DoctorWorkingHour $workingHour,
        Doctor $doctor
    ): void {
        abort_unless(
            (int) $workingHour->doctor_id === (int) $doctor->id &&
            in_array(
                (int) $workingHour->clinic_id,
                $this->accessibleClinicIds($doctor),
                true
            ),
            403,
            __('working_hours.forbidden')
        );
    }
}
