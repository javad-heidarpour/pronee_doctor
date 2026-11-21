<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\DoctorArticle;
use Inertia\Inertia;
use Inertia\Response;

final class PublicDoctorArticleController extends Controller
{
    public function index(Doctor $doctor): Response
    {
        abort_unless($doctor->is_active, 404);

        $articles = DoctorArticle::query()->where('doctor_id', $doctor->id)
            ->published()->latest('published_at')
            ->paginate(12)->through(fn (DoctorArticle $article) => [
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'body' => $article->body,
                'published_at' => $article->published_at?->toIso8601String(),
                'meta_title' => $article->meta_title,
                'meta_description' => $article->meta_description,
            ]);

        return Inertia::render('DoctorArticles/Index', [
            'doctor' => [
                'name' => $doctor->name,
                'family' => $doctor->family,
                'caption' => $doctor->caption,
                'slug' => $doctor->slug,
            ],
            'articles' => $articles,
        ]);
    }
}
