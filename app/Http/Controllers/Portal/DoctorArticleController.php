<?php

declare(strict_types=1);

namespace App\\Http\\Controllers\\Portal;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Controllers\\Portal\\Concerns\\HasActiveDoctor;
use App\\Models\\DoctorArticle;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Str;
use Inertia\\Inertia;
use Inertia\\Response;

final class DoctorArticleController extends Controller
{
    use HasActiveDoctor;

    public function index(): Response
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();

        return Inertia::render('Portal/Articles/Index', [
            'doctor' => $doctor,
            'articles' => DoctorArticle::query()->where('doctor_id', $doctor->id)
                ->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $data = $this->validated($request);

        DoctorArticle::query()->create([
            ...$data,
            'doctor_id' => $doctor->id,
            'slug' => $this->uniqueSlug($doctor->id, $data['title']),
            'status' => 'draft',
        ]);

        return back()->with('success', 'مقاله به‌صورت پیش‌نویس ذخیره شد.');
    }

    public function update(Request $request, DoctorArticle $article): RedirectResponse
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $article = $this->ownedArticle($doctor->id, $article);
        $data = $this->validated($request);

        $article->update([
            ...$data,
            'slug' => $this->uniqueSlug($doctor->id, $data['title'], $article->id),
        ]);

        return back()->with('success', 'مقاله بروزرسانی شد.');
    }

    public function publish(DoctorArticle $article): RedirectResponse
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $article = $this->ownedArticle($doctor->id, $article);
        abort_if(trim((string) $article->body) === '', 422, 'متن مقاله خالی است.');

        $article->update([
            'status' => 'published',
            'published_at' => $article->published_at ?? now(),
        ]);

        return back()->with('success', 'مقاله منتشر شد.');
    }

    public function unpublish(DoctorArticle $article): RedirectResponse
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $article = $this->ownedArticle($doctor->id, $article);
        $article->update(['status' => 'draft', 'published_at' => null]);

        return back()->with('success', 'مقاله از انتشار خارج شد.');
    }

    public function destroy(DoctorArticle $article): RedirectResponse
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $this->ownedArticle($doctor->id, $article)->delete();

        return back()->with('success', 'مقاله حذف شد.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:180'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'min:30', 'max:50000'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ]);
    }

    private function ownedArticle(int $doctorId, DoctorArticle $article): DoctorArticle
    {
        return DoctorArticle::query()->where('doctor_id', $doctorId)
            ->whereKey($article->id)->firstOrFail();
    }

    private function uniqueSlug(int $doctorId, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $base = $base !== '' ? $base : 'article';
        $slug = $base;
        $suffix = 2;

        while (DoctorArticle::query()->where('doctor_id', $doctorId)
            ->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
