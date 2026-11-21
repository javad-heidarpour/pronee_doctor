<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Patient;
use App\Models\PatientFollowUpTask;
use App\Models\PatientHealthObservation;
use App\Models\PatientMedicationPlan;
use App\Services\ClinicalRiskRadar;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class ClinicalRiskRadarController extends Controller
{
    use HasActiveDoctor;

    public function index(
        Request $request,
        ClinicalRiskRadar $radar,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.view', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        abort_if(empty($clinicIds), 403, 'هیچ مطب فعالی برای بررسی در دسترس نیست.');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'priority' => ['nullable', 'in:all,urgent,review,routine,unknown'],
        ]);

        $patients = Patient::query()
            ->where('doctor_id', $doctor->id)
            ->whereHas('appointments', fn ($query) => $query->whereIn('clinic_id', $clinicIds))
            ->with([
                'vitalSigns' => fn ($query) => $query
                    ->where('doctor_id', $doctor->id)
                    ->where('measured_at', '>=', now()->subDays(90))
                    ->orderByDesc('measured_at')->limit(40),
            ])
            ->withCount(['appointments' => fn ($query) => $query->whereIn('clinic_id', $clinicIds)])
            ->orderBy('name')
            ->get(['id', 'doctor_id', 'name', 'phone']);

        $patientIds = $patients->pluck('id');
        if ($patientIds->isEmpty()) {
            $results = collect();
        } else {
            $followUps = PatientFollowUpTask::query()
                ->where('doctor_id', $doctor->id)
                ->where(function ($query) use ($clinicIds): void {
                    $query->whereIn('clinic_id', $clinicIds)->orWhereNull('clinic_id');
                })
                ->whereIn('patient_id', $patientIds)
                ->whereIn('status', ['pending', 'in_progress'])
                ->orderBy('due_at')
                ->get(['id', 'patient_id', 'title', 'due_at', 'status'])
                ->groupBy('patient_id');

            $observations = PatientHealthObservation::query()
                ->whereIn('patient_id', $patientIds)
                ->where('observed_at', '>=', now()->subDays(30))
                ->orderByDesc('observed_at')
                ->get(['id', 'patient_id', 'measurement_type', 'value', 'unit', 'observed_at'])
                ->groupBy('patient_id');

            $medicationPlans = PatientMedicationPlan::query()
                ->whereIn('patient_id', $patientIds)
                ->where('status', 'active')
                ->whereHas('prescription.encounter', fn ($query) => $query
                    ->where('doctor_id', $doctor->id)
                    ->whereIn('clinic_id', $clinicIds))
                ->with(['doses' => fn ($query) => $query
                    ->where('scheduled_for', '>=', now()->subDays(7))
                    ->where('scheduled_for', '<=', now())
                    ->orderByDesc('scheduled_for')])
                ->get(['id', 'patient_id', 'user_id', 'prescription_id', 'status'])
                ->groupBy('patient_id');

            $results = $patients->map(fn (Patient $patient) => $radar->evaluate(
                $patient,
                $followUps->get($patient->id, collect()),
                $observations->get($patient->id, collect()),
                $medicationPlans->get($patient->id, collect())
            ));
        }

        $stats = [
            'total' => $results->count(),
            'urgent' => $results->where('priority', 'urgent')->count(),
            'review' => $results->where('priority', 'review')->count(),
            'routine' => $results->where('priority', 'routine')->count(),
            'unknown' => $results->where('priority', 'unknown')->count(),
        ];

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $results = $results->filter(fn ($item) => mb_stripos($item['patient_name'], $search) !== false);
        }

        $priority = $filters['priority'] ?? 'all';
        if ($priority !== 'all') {
            $results = $results->where('priority', $priority);
        }

        $priorityOrder = ['urgent' => 0, 'review' => 1, 'routine' => 2, 'unknown' => 3];
        $results = $results->sortBy(fn ($item) => sprintf('%d-%05d-%s', $priorityOrder[$item['priority']] ?? 9, 99999 - $item['flags_count'], $item['patient_name']))->values();

        $audit->handle('clinical_risk_radar.viewed', null, [
            'doctor_id' => $doctor->id,
            'clinic_count' => count($clinicIds),
            'result_count' => $results->count(),
        ]);

        return Inertia::render('Portal/ClinicalRiskRadar/Index', [
            'doctor' => $doctor,
            'patients' => $results,
            'stats' => $stats,
            'filters' => ['search' => $search, 'priority' => $priority],
            'generatedAt' => now()->toIso8601String(),
        ]);
    }
}
