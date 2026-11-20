<?php

declare(strict_types=1);

namespace App\\Http\\Controllers;

use App\\Actions\\Portal\\DoctorProfile\\ChangeDoctorPasswordAction;
use App\\Actions\\Portal\\Profile\\DeleteProfileAction;
use App\\Actions\\Portal\\Profile\\UpdateProfileAction;
use App\\Http\\Requests\\Profile\\DeleteProfileRequest;
use App\\Http\\Requests\\Profile\\UpdateProfilePasswordRequest;
use App\\Http\\Requests\\Profile\\UpdateProfileRequest;
use App\\Models\\User;
use Illuminate\\Contracts\\Auth\\MustVerifyEmail;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Inertia\\Inertia;
use Inertia\\Response;

final class ProfileController extends Controller
{
    public function __construct(
        private readonly UpdateProfileAction $updateAction,
        private readonly DeleteProfileAction $deleteAction,
    ) {
    }

    public function edit(Request $request): Response
    {
        $user = $request->user('web');
        abort_unless($user instanceof User && $user->isDoctor(), 403);

        $doctor = $user->doctorProfile;
        abort_unless($doctor !== null, 403);

        // The database stores a relative storage path; expose its public URL to Inertia.
        $doctor->append('photo_url');

        return Inertia::render('Portal/Profile', [
            'doctor' => $doctor,
            'mustVerifyEmail' => $user instanceof MustVerifyEmail
                && ! $user->hasVerifiedEmail(),
            'status' => session('status'),
        ]);
    }

    public function updatePassword(
        UpdateProfilePasswordRequest $request,
        ChangeDoctorPasswordAction $action
    ): RedirectResponse {
        $user = $request->user('web');

        abort_unless($user instanceof User, 401);

        $changed = $action->handle(
            $user,
            $request->string('current_password')->toString(),
            $request->string('password')->toString(),
            $request->session()->getId()
        );

        if (! $changed) {
            return back()->withErrors([
                'current_password' => 'رمز عبور فعلی صحیح نیست.',
            ]);
        }

        return back()->with('success', 'رمز عبور حساب کاربری با موفقیت تغییر کرد.');
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        return $this->updateAction->handle($request);
    }

    public function destroy(DeleteProfileRequest $request): RedirectResponse
    {
        return $this->deleteAction->handle($request);
    }
}
