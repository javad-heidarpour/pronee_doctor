<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Portal\Profile\DeleteProfileAction;
use App\Actions\Portal\Profile\EditProfileAction;
use App\Actions\Portal\Profile\UpdateProfileAction;

use App\Actions\Profile\ChangeProfilePasswordAction;
use App\Http\Requests\Profile\DeleteProfileRequest;
use App\Http\Requests\Profile\UpdateProfilePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\Request;




class ProfileController extends Controller
{
    public function __construct(

        private readonly EditProfileAction $editAction,

        private readonly UpdateProfileAction $updateAction,

        private readonly DeleteProfileAction $deleteAction,

    ) {
    }



    public function updatePassword(UpdateProfilePasswordRequest $request, ChangeProfilePasswordAction $action)
    {
        $doctor = $request->user('web')->doctorProfile;

        try {
            $changed = $action->handle(
                $doctor,
                $request->string('current_password')->toString(),
                $request->string('password')->toString()
            );
        } catch (\\RuntimeException $exception) {
            return back()->withErrors([
                'current_password' => 'رمز ذخیره‌شده قابل بررسی نیست. لطفاً با مدیر سامانه تماس بگیرید.',
            ]);
        }

        if (! $changed) {
            return back()->withErrors([
                'current_password' => 'رمز عبور فعلی صحیح نیست.',
            ]);
        }

        return back()->with('success', 'رمز عبور با موفقیت تغییر کرد.');
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