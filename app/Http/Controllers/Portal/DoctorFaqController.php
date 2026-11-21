<?php

declare(strict_types=1);

namespace App\\Http\\Controllers\\Portal;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Controllers\\Portal\\Concerns\\HasActiveDoctor;
use App\\Models\\DoctorFaq;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Inertia\\Inertia;
use Inertia\\Response;

final class DoctorFaqController extends Controller
{
    use HasActiveDoctor;

    public function index(): Response
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();

        return Inertia::render('Portal/Faqs/Index', [
            'doctor' => $doctor,
            'faqs' => DoctorFaq::query()->where('doctor_id', $doctor->id)
                ->orderBy('sort_order')->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $data = $this->validated($request);
        $data['doctor_id'] = $doctor->id;
        $data['published_at'] = ($data['is_published'] ?? false) ? now() : null;
        DoctorFaq::query()->create($data);

        return back()->with('success', 'پرسش متداول ثبت شد.');
    }

    public function update(Request $request, int $faq): RedirectResponse
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $item = DoctorFaq::query()->where('doctor_id', $doctor->id)->findOrFail($faq);
        $data = $this->validated($request);
        $data['published_at'] = ($data['is_published'] ?? false)
            ? ($item->published_at ?? now())
            : null;
        $item->update($data);

        return back()->with('success', 'پرسش متداول ویرایش شد.');
    }

    public function destroy(int $faq): RedirectResponse
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        DoctorFaq::query()->where('doctor_id', $doctor->id)->findOrFail($faq)->delete();

        return back()->with('success', 'پرسش متداول حذف شد.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'question' => ['required', 'string', 'min:5', 'max:240'],
            'answer' => ['required', 'string', 'min:5', 'max:5000'],
            'category' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_published' => ['required', 'boolean'],
        ]);
    }
}
