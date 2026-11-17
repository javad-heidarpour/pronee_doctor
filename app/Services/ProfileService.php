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
        $doctor = $request->user('web')?->doctorProfile;

        abort_unless($doctor !== null, 403);

        return Inertia::render('Portal/Profile', [
            'doctor' => $doctor,
            'mustVerifyEmail' => $doctor instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    public function update(UpdateProfileDto $dto): RedirectResponse
    {
        DB::transaction(function () use ($dto): void {
            $data = $dto->toArray();

            if ($dto->doctor->email !== $dto->email) {
                $data['email_verified_at'] = null;
            }

            if ($dto->photo !== null) {
                $path = $dto->photo->store('doctors', 'public');
                $data['photo'] = $path;
            }

            $this->repository->update($dto->doctor, $data);
        });

        return back()->with(
            'success',
            'اطلاعات پروفایل با موفقیت به‌روزرسانی شد.'
        );
    }

    public function destroy(DeleteProfileDto $dto): RedirectResponse
    {
        DB::transaction(function () use ($dto): void {
            $this->repository->delete($dto->doctor);
        });

        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return Redirect::route('home');
    }
}
