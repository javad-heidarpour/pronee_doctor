<?php

declare(strict_types=1);

namespace App\\Http\\Controllers\\Portal;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Controllers\\Portal\\Concerns\\HasActiveDoctor;
use App\\Models\\AppointmentSatisfactionSurvey;
use Inertia\\Inertia;
use Inertia\\Response;

final class AppointmentSatisfactionReportController extends Controller
{
    use HasActiveDoctor;

    public function index(): Response
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('appointments.view', $doctor);
        $query = AppointmentSatisfactionSurvey::query()->where('doctor_id', $doctor->id);
        $summary = (clone $query)->selectRaw(
            'COUNT(*) AS responses, AVG(overall_rating) AS overall, AVG(communication_rating) AS communication, AVG(waiting_rating) AS waiting, AVG(facility_rating) AS facility'
        )->first();

        return Inertia::render('Portal/Satisfaction/Index', [
            'doctor' => $doctor,
            'summary' => [
                'responses' => (int) ($summary->responses ?? 0),
                'overall' => round((float) ($summary->overall ?? 0), 2),
                'communication' => round((float) ($summary->communication ?? 0), 2),
                'waiting' => round((float) ($summary->waiting ?? 0), 2),
                'facility' => round((float) ($summary->facility ?? 0), 2),
            ],
            'surveys' => (clone $query)->latest()->paginate(20)->withQueryString(),
        ]);
    }
}
