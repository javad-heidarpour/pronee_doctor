<?php

declare(strict_types=1);

namespace App\\Http\\Controllers;

use App\\Models\\Appointment;
use App\\Models\\AppointmentSatisfactionSurvey;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Inertia\\Inertia;
use Inertia\\Response;

final class AppointmentSatisfactionController extends Controller
{
    public function show(Appointment $appointment): Response
    {
        abort_unless(in_array($appointment->status, ['visited', 'completed'], true), 404);
        return Inertia::render('Satisfaction/Show', [
            'submitted' => $appointment->satisfactionSurvey()->exists(),
        ]);
    }

    public function store(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate([
            'overall_rating' => ['required', 'integer', 'between:1,5'],
            'communication_rating' => ['required', 'integer', 'between:1,5'],
            'waiting_rating' => ['required', 'integer', 'between:1,5'],
            'facility_rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'is_anonymous' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($appointment, $data): void {
            $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, ['visited', 'completed'], true), 404);
            abort_if($locked->satisfactionSurvey()->exists(), 409, 'برای این نوبت قبلاً نظرسنجی ثبت شده است.');

            AppointmentSatisfactionSurvey::query()->create([
                ...$data,
                'appointment_id' => $locked->id,
                'doctor_id' => $locked->doctor_id,
            ]);
        });

        return back()->with('success', 'از اینکه تجربه خود را ثبت کردید سپاسگزاریم.');
    }
}
