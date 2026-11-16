<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\DoctorProfile\ChangeDoctorPasswordAction;
use App\Actions\Portal\DoctorProfile\ShowDoctorProfileAction;
use App\Actions\Portal\DoctorProfile\UpdateDoctorProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UpdateDoctorPasswordRequest;
use App\Http\Requests\Portal\UpdateDoctorProfileRequest;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit(ShowDoctorProfileAction $action)
    {
        return Inertia::render('Portal/Profile', ['doctor' => $action->handle()]);
    }

    public function update(UpdateDoctorProfileRequest $request, UpdateDoctorProfileAction $action)
    {
        $action->handle(
            $request->validated(),
            $request->file('photo'),
        );

        return back()->with('success', 'اطلاعات پروفایل با موفقیت بروزرسانی شد.');
    }

    public function updatePassword(UpdateDoctorPasswordRequest $request, ChangeDoctorPasswordAction $action)
    {
        $validated = $request->validated();

        if (!$action->handle($validated['current_password'], $validated['password'])) {
            return back()->withErrors(['current_password' => 'رمز عبور فعلی اشتباه است.']);
        }

        return back()->with('success', 'رمز عبور با موفقیت تغییر کرد.');
    }
}
