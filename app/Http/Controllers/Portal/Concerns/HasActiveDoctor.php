<?php

namespace App\Http\Controllers\Portal\Concerns;

use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;

trait HasActiveDoctor
{
    protected function currentDoctor(): Doctor
    {
        $user = Auth::guard('web')->user();

        abort_unless($user, 401, 'کاربر وارد نشده است.');

        $doctors = $user->accessibleDoctors();
        $doctorId = session('active_doctor_id');

        $doctor = $doctorId
            ? (clone $doctors)->whereKey($doctorId)->first()
            : null;

        if (!$doctor) {
            session()->forget('active_doctor_id');

            $availableDoctors = (clone $doctors)->get();

            abort_if(
                $availableDoctors->isEmpty(),
                403,
                'هیچ پرونده پزشک فعالی برای این حساب کاربری در دسترس نیست.'
            );

            abort_if(
                $availableDoctors->count() > 1,
                403,
                'ابتدا پزشک فعال را از داشبورد انتخاب کنید.'
            );

            $doctor = $availableDoctors->first();
            session(['active_doctor_id' => $doctor->id]);
        }

        return $doctor;
    }
}