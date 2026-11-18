<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Patient;
use App\Models\PatientFollowUpTask;
use App\Models\PatientVitalSign;
use App\Services\PredictivePatientEngine;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class PredictivePatientController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request, PredictivePatientEngine $engine, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.view', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        abort_if($clinicIds === [], 403, 'هیچ مطب فعالی برای بررسی در دسترس نیست.');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'attention' => ['nullable', 'in:all,yes,no'],
        ]);

        $patients = Patient::query()
            ->where('doctor_id', $doctor->id)
            ->whereHas('appointments', fn ($q) => $q->whereIn('clinic_id', $clinicIds))
            ->orderBy('name')
            ->get(['id', 'doctor_id', 'name']);

        $ids = $patients->pluck('id');
        $measurements = $ids->isEmpty() ? collect() : PatientVitalSign::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('patient_id', $ids)
            ->where('measured_at', '>=', now()->subDays(120))
            ->orderBy('measured_at')
            ->get(['id', 'patient_id', 'measurement_type', 'value', 'unit', 'measured_at'])
            ->groupBy('patient_id');

        $followUps = $ids->isEmpty() ? collect() : PatientFollowUpTask::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('patient_id', $ids)
            ->where(fn ($q) => $q->whereIn('clinic_id', $clinicIds)->orWhereNull('clinic_id'))
            ->whereIn('status', ['pending', 'in_progress'])
            ->get(['id', 'patient_id', 'title', 'due_at', 'status'])
            ->groupBy('patient_id');

        $results = $patients->map(fn (Patient $patient) => $engine->evaluate(
            $patient,
            $measurements->get($patient->id, collect()),
            $followUps->get($patient->id, collect())
        ));

        $stats = [
            'total' => $results->count(),
            'needs_review' => $results->where('needs_review', true)->count(),
            'strong' => $results->where('confidence', 'strong')->count(),
            'insufficient' => $results->where('confidence', 'insufficient')->count(),
        ];

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') $results = $results->filter(fn ($item) => mb_stripos($item['patient_name'], $search) !== false);
        $attention = $filters['attention'] ?? 'all';
        if ($attention !== 'all') $results = $results->where('needs_review', $attention === 'yes');
        $results = $results->sortByDesc(fn ($item) => [$item['needs_review'] ? 1 : 0, count($item['signals']), $item['overdue_followups']])->values();

        $audit->handle('predictive_patient_engine.viewed', null, [
            'doctor_id' => $doctor->id,
            'clinic_count' => count($clinicIds),
            'patient_count' => $results->count(),
        ]);

        return Inertia::render('Portal/PredictivePatients/Index', [
            'doctor' => $doctor,
            'patients' => $results,
            'stats' => $stats,
            'filters' => ['search' => $search, 'attention' => $attention],
            'generatedAt' => now()->toIso8601String(),
        ]);
    }
}
