<?php

declare(strict_types=1);

namespace App\Actions\Portal\Clinic;

use App\Models\Doctor;

final class EnsureDoctorOwnsActiveClinicAction
{
    public function handle(Doctor $doctor, int $clinicId): void
    {
        abort_unless(
            $doctor->address()
                ->whereKey($clinicId)
                ->where('is_active', true)
                ->exists(),
            403,
            'مطب انتخاب‌شده متعلق به پزشک فعال نیست یا غیرفعال شده است.'
        );
    }
}
