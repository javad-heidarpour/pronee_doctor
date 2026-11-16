<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\DoctorProfile\ChangeDoctorPasswordAction;
use App\Actions\Portal\DoctorProfile\ShowDoctorProfileAction;
use App\Actions\Portal\DoctorProfile\UpdateDoctorProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Http\Requests\Portal\UpdateDoctorPasswordRequest;
use App\Http\Requests\Portal\UpdateDoctorProfileRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProfileController extends Controller
{
    use HasActiveDoctor;

    public function edit(ShowDoctorProfileAction $action)
    {
        $doctor = $this->currentDoctor();

        return Inertia::render('Portal/Profile', [
            'doctor' => $action->handle($doctor),
        ]);
    }

    public function update(
        UpdateDoctorProfileRequest $request,
        UpdateDoctorProfileAction $action
    ) {
        $doctor = $this->currentDoctor();

        $action->handle(
            $doctor,
            $request->validated(),
            $request->file('photo')
        );

        return back()->with(
            'success',
            'اطلاعات پروفایل با موفقیت بروزرسانی شد.'
        );
    }

    public function updatePassword(
        UpdateDoctorPasswordRequest $request,
        ChangeDoctorPasswordAction $action
    ) {
        $user = Auth::guard('web')->user();

        abort_unless($user, 401);

        $validated = $request->validated();

        if (!$action->handle(
            $user,
            $validated['current_password'],
            $validated['password']
        )) {
            return back()->withErrors([
                'current_password' => 'رمز عبور فعلی اشتباه است.',
            ]);
        }

        return back()->with(
            'success',
            'رمز عبور حساب کاربری با موفقیت تغییر کرد.'
        );
    }
}