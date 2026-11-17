<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Portal\Profile\DeleteProfileDto;
use App\DTOs\Portal\Profile\UpdateProfileDto;
use App\Repositories\Contracts\DoctorRepositoryInterface;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class ProfileService
{
    public function __construct(
        private readonly DoctorRepositoryInterface $repository,
    ) {
    }

    public function edit(Request $request): Response
    {
        $user = $request->user('web');
        $doctor = $user?->doctorProfile;

        abort_unless($user?->isDoctor() && $doctor !== null, 403);

        $user = $request->user('web');

        return Inertia::render('Portal/Profile', [
            'doctor' => $doctor,
            'mustVerifyEmail' => $user instanceof MustVerifyEmail
                && ! $user->hasVerifiedEmail(),
            'status' => session('status'),
        ]);
    }

    public function update(UpdateProfileDto $dto): RedirectResponse
    {
        DB::transaction(function () use ($dto): void {
            $doctor = $dto->doctor;
            $emailChanged = $doctor->email !== $dto->email;
            $data = $dto->toArray();

            if ($dto->photo !== null) {
                $data['photo'] = $dto->photo->store('doctors', 'public');
            }

            $this->repository->update($doctor, $data);

            // Keep the public doctor profile and the web login identity in sync.
            $user = $doctor->user;

            if ($user !== null) {
                $user->forceFill([
                    'name' => $dto->name,
                    'family' => $dto->family,
                    'email' => $dto->email,
                    'phone' => $dto->phone,
                    ...($emailChanged ? ['email_verified_at' => null] : []),
                ])->save();
            }
        });

        return back()->with(
            'success',
            'اطلاعات پروفایل با موفقیت به‌روزرسانی شد.'
        );
    }

    public function destroy(DeleteProfileDto $dto): RedirectResponse
    {
        DB::transaction(function () use ($dto): void {
            $user = $dto->doctor->user;

            $this->repository->delete($dto->doctor);

            if ($user !== null) {
                $user->forceFill(['is_active' => false])->save();
                $user->delete();
            }
        });

        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return Redirect::route('home');
    }
}
