<?php

namespace App\Http\Controllers\Portal\Concerns;

use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;

trait HasActiveDoctor
{
    protected function currentDoctor(): Doctor
    {
        $user = Auth::guard('web')->user();

        abort_unless(
            $user,
            401,
            'کاربر وارد نشده است.'
        );

        $doctorId = session('active_doctor_id');

        abort_unless(
            $doctorId,
            403,
            'پزشک فعالی انتخاب نشده است.'
        );

        $doctor = $user->doctors()
            ->where('doctors.id', $doctorId)
            ->where('doctors.is_active', true)
            ->first();

        abort_unless(
            $doctor,
            403,
            'پروفایل پزشک یافت نشد یا دسترسی به آن مجاز نیست.'
        );

        return $doctor;
    }
}