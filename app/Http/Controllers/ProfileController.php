<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Portal\Profile\DeleteProfileAction;
use App\Actions\Portal\Profile\EditProfileAction;
use App\Actions\Portal\Profile\UpdateProfileAction;

use App\Http\Requests\Profile\DeleteProfileRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;




class ProfileController extends Controller
{
    public function __construct(

        private readonly EditProfileAction $editAction,

        private readonly UpdateProfileAction $updateAction,

        private readonly DeleteProfileAction $deleteAction,

    ) {
    }



  public function updatePassword(Request $request)
{
    // دریافت کاربر از گارد اصلی
        $user = Auth::guard('web')->user();

        abort_unless(
            $user,
            401,
            'کاربر وارد نشده است.'
        );

        // دریافت پروفایل پزشک
        $doctor = $user->doctorProfile;

        abort_unless(
            $doctor,
            403,
            'پروفایل پزشک برای این حساب کاربری وجود ندارد.'
        );

    if (!$doctor) {
        abort(401, 'ابتدا وارد حساب کاربری شوید.');
    }

    $request->validate([
        'current_password' => ['required', 'string'],
        'password' => [
            'required',
            'string',
            'confirmed',
            \Illuminate\Validation\Rules\Password::defaults(),
        ],
    ], [
        'current_password.required' => 'رمز عبور فعلی الزامی است.',
        'password.required' => 'رمز عبور جدید الزامی است.',
        'password.confirmed' => 'تکرار رمز عبور جدید مطابقت ندارد.',
    ]);

    $storedPassword = $doctor->getAuthPassword();
    $currentPassword = $request->input('current_password');

    try {
        $passwordIsValid = \Illuminate\Support\Facades\Hash::check(
            $currentPassword,
            $storedPassword
        );
    } catch (\RuntimeException $e) {
        \Illuminate\Support\Facades\Log::warning(
            'Stored password hash is incompatible with the configured hasher.',
            ['doctor_id' => $doctor->getAuthIdentifier()]
        );

        return back()->withErrors([
            'current_password' =>
                'رمز ذخیره‌شده قابل بررسی نیست. لطفاً با مدیر سامانه تماس بگیرید.',
        ]);
    }

    if (!$passwordIsValid) {
        return back()->withErrors([
            'current_password' => 'رمز عبور فعلی صحیح نیست.',
        ]);
    }

    $doctor->forceFill([
        'password' => \Illuminate\Support\Facades\Hash::make(
            $request->input('password')
        ),
    ])->save();

    return back()->with(
        'success',
        'رمز عبور با موفقیت تغییر کرد.'
    );
}
    public function edit(Request $request)
    {
        return $this->editAction->handle($request);
    }

    public function update(UpdateProfileRequest $request)
    {
        return $this->updateAction->handle($request);
    }

    public function destroy(DeleteProfileRequest $request)
    {
        return $this->deleteAction->handle($request);
    }
}