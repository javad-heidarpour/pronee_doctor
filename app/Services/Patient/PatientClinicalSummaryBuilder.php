<?php

declare(strict_types=1);

namespace App\Services\Patient;

use Illuminate\Support\Collection;

final class PatientClinicalSummaryBuilder
{
    public function build(
        string $patientName,
        Collection $encounters,
        Collection $problems,
        Collection $clinicalRecords,
        Collection $vitals,
        Collection $followUps,
        Collection $pathways
    ): array {
        $latestEncounter = $encounters->sortByDesc('created_at')->first();

        return [
            'patient_name' => $patientName,
            'generated_at' => now()->toIso8601String(),
            'latest_encounter' => $latestEncounter ? [
                'date' => $latestEncounter->created_at?->toIso8601String(),
                'diagnosis' => $latestEncounter->diagnosis,
                'assessment' => $latestEncounter->assessment,
                'plan' => $latestEncounter->plan,
                'status' => $latestEncounter->status,
            ] : null,
            'active_problems' => $problems->where('status', 'active')->take(12)->map(fn ($item) => [
                'title' => $item->title,
                'severity' => $item->severity,
                'onset_date' => $item->onset_date?->toDateString(),
            ])->values(),
            'allergies' => $clinicalRecords->where('kind', 'allergy')->where('is_active', true)->take(12)->map(fn ($item) => [
                'title' => $item->title,
                'description' => $item->description,
            ])->values(),
            'medications' => $clinicalRecords->where('kind', 'medication')->where('is_active', true)->take(20)->map(fn ($item) => [
                'title' => $item->title,
                'dosage' => $item->dosage,
                'frequency' => $item->frequency,
                'start_date' => $item->start_date?->toDateString(),
                'end_date' => $item->end_date?->toDateString(),
            ])->values(),
            'latest_vitals' => $vitals->sortByDesc('measured_at')->groupBy('measurement_type')
                ->map(fn ($items) => $items->first())
                ->map(fn ($item) => [
                    'type' => $item->measurement_type,
                    'value' => $item->value,
                    'unit' => $item->unit,
                    'measured_at' => $item->measured_at?->toIso8601String(),
                ])->values(),
            'open_follow_ups' => $followUps->whereIn('status', ['pending','in_progress'])->take(20)->map(fn ($item) => [
                'title' => $item->title,
                'due_at' => $item->due_at?->toIso8601String(),
                'status' => $item->status,
                'priority' => $item->priority,
            ])->values(),
            'active_pathways' => $pathways->whereIn('status', ['active','in_progress'])->take(12)->map(fn ($item) => [
                'title' => $item->title,
                'goal' => $item->goal,
                'status' => $item->status,
                'steps_total' => $item->steps->count(),
                'steps_verified' => $item->steps->where('status','verified')->count(),
            ])->values(),
            'scope' => [
                'encounters_loaded' => $encounters->count(),
                'problems_loaded' => $problems->count(),
                'records_loaded' => $clinicalRecords->count(),
                'vitals_loaded' => $vitals->count(),
                'follow_ups_loaded' => $followUps->count(),
                'pathways_loaded' => $pathways->count(),
            ],
            'limitations' => [
                'summary_of_recorded_data_only',
                'not_a_diagnosis_or_treatment_recommendation',
                'missing_data_does_not_mean_normal',
                'verify_with_patient_and_source_record',
            ],
        ];
    }
}
