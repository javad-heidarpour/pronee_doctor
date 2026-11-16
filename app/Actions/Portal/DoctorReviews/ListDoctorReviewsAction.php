<?php

namespace App\Actions\Portal\DoctorReviews;

use App\Models\Doctor;
use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ListDoctorReviewsAction
{
    public function handle(Request $request, Doctor $doctor)
    {
        $reviews = Review::query()
            ->where('doctor_id', $doctor->id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search')->toString());

                $query->where(function ($q) use ($search) {
                    $q->where('patient_name', 'like', "%{$search}%")
                        ->orWhere('comment', 'like', "%{$search}%");
                });
            })
            ->when($request->input('status') === 'approved', fn ($query) => $query->where('is_approved', true))
            ->when($request->input('status') === 'pending', fn ($query) => $query->where('is_approved', false))
            ->when($request->filled('rating'), fn ($query) => $query->where('rating', $request->integer('rating')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'count' => Review::where('doctor_id', $doctor->id)->count(),
            'approved' => Review::where('doctor_id', $doctor->id)
                ->where('is_approved', true)
                ->count(),
            'pending' => Review::where('doctor_id', $doctor->id)
                ->where('is_approved', false)
                ->count(),
            'average' => round(
                Review::where('doctor_id', $doctor->id)
                    ->where('is_approved', true)
                    ->avg('rating') ?? 0,
                1
            ),
        ];

        return Inertia::render('Portal/DoctorReviews', [
            'doctor' => $doctor,
            'reviews' => $reviews,
            'stats' => $stats,
            'filters' => [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'rating' => $request->input('rating'),
            ],
        ]);
    }
}