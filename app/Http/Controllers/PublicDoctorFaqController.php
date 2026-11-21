<?php

declare(strict_types=1);

namespace App\\Http\\Controllers;

use App\\Models\\Doctor;
use App\\Models\\DoctorFaq;
use Inertia\\Inertia;
use Inertia\\Response;

final class PublicDoctorFaqController extends Controller
{
    public function show(string $slug): Response
    {
        $doctor = Doctor::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();

        return Inertia::render('HomePage/Faq', [
            'doctor' => $doctor->hidePrivatePublicFields(),
            'faqs' => DoctorFaq::query()->where('doctor_id', $doctor->id)
                ->published()->orderBy('sort_order')->orderBy('id')
                ->get(['id', 'question', 'answer', 'category']),
        ]);
    }
}
