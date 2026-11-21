<?php

declare(strict_types=1);

namespace App\\Http\\Controllers\\Portal;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Controllers\\Portal\\Concerns\\HasActiveDoctor;
use App\\Models\\DoctorSocial;
use App\\Models\\SocialPlatform;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Inertia\\Inertia;
use Inertia\\Response;

final class DoctorSocialController extends Controller
{
    use HasActiveDoctor;

    public function index(): Response
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();

        return Inertia::render('Portal/Socials/Index', [
            'doctor' => $doctor,
            'platforms' => SocialPlatform::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'title']),
            'socials' => DoctorSocial::query()->where('doctor_id', $doctor->id)->with('platform:id,name,title')
                ->orderBy('id')->get(['id', 'doctor_id', 'social_platform_id', 'username', 'url', 'is_visible']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $data = $this->validated($request);
        DoctorSocial::query()->updateOrCreate(
            ['doctor_id' => $doctor->id, 'social_platform_id' => $data['social_platform_id']],
            ['username' => $data['username'] ?? null, 'url' => $data['url'] ?? null, 'is_visible' => $data['is_visible']]
        );

        return back()->with('success', 'راه ارتباطی ذخیره شد.');
    }

    public function update(Request $request, int $social): RedirectResponse
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $item = DoctorSocial::query()->where('doctor_id', $doctor->id)->findOrFail($social);
        $data = $this->validated($request);
        $duplicate = DoctorSocial::query()->where('doctor_id', $doctor->id)
            ->where('social_platform_id', $data['social_platform_id'])->whereKeyNot($item->id)->exists();
        if ($duplicate) {
            return back()->withErrors(['social_platform_id' => 'این شبکه اجتماعی قبلاً ثبت شده است.']);
        }
        $item->update([
            'social_platform_id' => $data['social_platform_id'],
            'username' => $data['username'] ?? null,
            'url' => $data['url'] ?? null,
            'is_visible' => $data['is_visible'],
        ]);

        return back()->with('success', 'راه ارتباطی ویرایش شد.');
    }

    public function destroy(int $social): RedirectResponse
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        DoctorSocial::query()->where('doctor_id', $doctor->id)->findOrFail($social)->delete();

        return back()->with('success', 'راه ارتباطی حذف شد.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'social_platform_id' => ['required', 'integer', 'exists:social_platforms,id'],
            'username' => ['nullable', 'string', 'max:190'],
            'url' => ['nullable', 'url:http,https', 'max:2048'],
            'is_visible' => ['required', 'boolean'],
        ]);
    }
}
