<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\PatientCarePathway;
use App\Models\PatientClinicalRecord;
use App\Models\PatientEncounter;
use App\Models\PatientFollowUpTask;
use App\Models\PatientMedicationPlan;
use App\Models\PatientVitalSign;
use App\Services\Patient\PatientClinicalSummaryBuilder;
use App\Services\Patient\PhysicianWorkflowAssistant;
use App\Models\PatientProblem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

final class Patient360Controller extends Controller
{
    use HasActiveDoctor;

    public function show(
        Patient $patient,
        RecordSecurityAuditEventAction $audit,
        PatientClinicalSummaryBuilder $summaryBuilder,
        PhysicianWorkflowAssistant $workflowAssistant
    )
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.view', $doctor);

        $clinicIds = $this->accessibleClinicIds($doctor);
        abort_if($clinicIds === [], 403, 'هیچ مطب فعالی برای مشاهده پرونده در دسترس نیست.');

        $patient = Patient::query()
            ->whereKey($patient->id)
            ->where('doctor_id', $doctor->id)
            ->whereHas('appointments', fn ($query) => $query->whereIn('clinic_id', $clinicIds))
            ->firstOrFail();

        $user = Auth::guard('web')->user();
        $isSecretary = $user?->role === 'secretary';
        $canViewClinical = !$isSecretary
            || $this->secretaryHasPermission($doctor, 'patients.clinical.view')
            || $this->secretaryHasPermission($doctor, 'patients.clinical.manage');

        $appointments = $patient->appointments()
            ->whereIn('clinic_id', $clinicIds)
            ->with('clinic:id,name')
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->limit(30)
            ->get(['id','patient_id','clinic_id','appointment_date','appointment_time','status','visit_number','fee_rial','visit_reason_id','created_at']);

        $timeline = collect();
        $encounters = collect();
        $clinicalRecords = collect();
        $vitals = collect();
        $followUps = collect();
        $pathways = collect();
        $medicationPlans = collect();
        $problems = collect();

        if ($canViewClinical) {
            $encounters = PatientEncounter::query()
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->whereIn('clinic_id', $clinicIds)
                ->with(['clinic:id,name', 'prescriptions'])
                ->orderByDesc('created_at')->limit(30)->get();

            $clinicalRecords = PatientClinicalRecord::query()
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->where('is_active', true)
                ->orderByDesc('updated_at')->limit(50)->get();

            $problems = PatientProblem::query()
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status = 'resolved' THEN 1 ELSE 2 END")
                ->orderByDesc('onset_date')
                ->orderByDesc('id')
                ->limit(100)
                ->get();

            $vitals = PatientVitalSign::query()
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->orderByDesc('measured_at')->limit(50)->get();

            $followUps = PatientFollowUpTask::query()
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->where(fn ($query) => $query->whereIn('clinic_id', $clinicIds)->orWhereNull('clinic_id'))
                ->orderByRaw("CASE WHEN status IN ('pending','in_progress') THEN 0 ELSE 1 END")
                ->orderBy('due_at')->limit(30)->get();

            $pathways = PatientCarePathway::query()
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->whereIn('clinic_id', $clinicIds)
                ->with('steps')
                ->orderByDesc('created_at')->limit(15)->get();

            $medicationPlans = PatientMedicationPlan::query()
                ->where('patient_id', $patient->id)
                ->whereHas('prescription.encounter', fn ($query) => $query
                    ->where('doctor_id', $doctor->id)
                    ->where('patient_id', $patient->id)
                    ->whereIn('clinic_id', $clinicIds))
                ->with(['prescription.encounter:id,clinic_id,created_at'])
                ->orderByDesc('created_at')->limit(20)->get();

            foreach ($encounters as $item) {
                $timeline->push($this->timelineItem('encounter', $item->created_at, 'ویزیت بالینی', $item->diagnosis ?: $item->assessment, $item->status, $item->id));
            }
            foreach ($clinicalRecords as $item) {
                $timeline->push($this->timelineItem($item->kind, $item->updated_at, $this->recordLabel($item->kind), $item->title, $item->is_active ? 'active' : 'inactive', $item->id));
            }
            foreach ($problems as $item) {
                $timeline->push($this->timelineItem('problem', $item->created_at, 'تشخیص / مشکل: '.$item->title, $item->description, $item->status, $item->id));
            }
            foreach ($vitals as $item) {
                $timeline->push($this->timelineItem('vital', $item->measured_at, 'اندازه‌گیری '.$item->measurement_type, $item->value.' '.$item->unit, 'recorded', $item->id));
            }
            foreach ($followUps as $item) {
                $timeline->push($this->timelineItem('follow_up', $item->due_at ?: $item->created_at, $item->title, $item->description, $item->status, $item->id));
            }
            foreach ($appointments as $item) {
                $timeline->push($this->timelineItem('appointment', $item->appointment_date, 'نوبت مراجعه', $item->clinic?->name, $item->status, $item->id));
            }
            foreach ($pathways as $item) {
                $timeline->push($this->timelineItem('pathway', $item->created_at, 'مسیر مراقبت: '.$item->title, $item->goal, $item->status, $item->id));
            }
        } else {
            foreach ($appointments as $item) {
                $timeline->push($this->timelineItem('appointment', $item->appointment_date, 'نوبت مراجعه', $item->clinic?->name, $item->status, $item->id));
            }
        }

        $timeline = $timeline->sortByDesc(fn (array $item) => $item['sort_at'] ?? '')->take(100)->values();

        // The recent timeline is intentionally capped, but the latest value of
        // every measurement type must not disappear just because another type
        // has many newer readings.
        $latestVitalTypes = $canViewClinical
            ? PatientVitalSign::query()
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->distinct()
                ->pluck('measurement_type')
            : collect();

        $latestVitalByType = $latestVitalTypes
            ->map(fn (string $type) => PatientVitalSign::query()
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->where('measurement_type', $type)
                ->orderByDesc('measured_at')
                ->orderByDesc('id')
                ->first())
            ->filter()
            ->values();

        // Summary cards report true totals, not the size of the capped lists
        // rendered below.
        $summary = [
            'appointments' => $patient->appointments()
                ->whereIn('clinic_id', $clinicIds)
                ->count(),
            'encounters' => $canViewClinical
                ? PatientEncounter::query()->where('doctor_id', $doctor->id)
                    ->where('patient_id', $patient->id)->whereIn('clinic_id', $clinicIds)->count()
                : 0,
            'active_allergies' => $canViewClinical
                ? PatientClinicalRecord::query()->where('doctor_id', $doctor->id)
                    ->where('patient_id', $patient->id)->where('kind', PatientClinicalRecord::KIND_ALLERGY)
                    ->where('is_active', true)->count()
                : 0,
            'active_medications' => $canViewClinical
                ? PatientClinicalRecord::query()->where('doctor_id', $doctor->id)
                    ->where('patient_id', $patient->id)->where('kind', PatientClinicalRecord::KIND_MEDICATION)
                    ->where('is_active', true)->count()
                : 0,
            'active_problems' => $canViewClinical
                ? PatientProblem::query()->where('doctor_id', $doctor->id)
                    ->where('patient_id', $patient->id)->where('status', PatientProblem::STATUS_ACTIVE)->count()
                : 0,
            'open_follow_ups' => $canViewClinical
                ? PatientFollowUpTask::query()->where('doctor_id', $doctor->id)
                    ->where('patient_id', $patient->id)
                    ->where(fn ($query) => $query->whereIn('clinic_id', $clinicIds)->orWhereNull('clinic_id'))
                    ->whereIn('status', ['pending', 'in_progress'])->count()
                : 0,
            'active_pathways' => $canViewClinical
                ? PatientCarePathway::query()->where('doctor_id', $doctor->id)
                    ->where('patient_id', $patient->id)->whereIn('clinic_id', $clinicIds)
                    ->whereIn('status', ['active', 'in_progress'])->count()
                : 0,
        ];

        $clinicalSummary = null;
        $workflowSuggestions = null;
        if ($canViewClinical) {
            $clinicalSummary = $summaryBuilder->build(
                (string) $patient->name,
                $encounters,
                $problems,
                $clinicalRecords,
                $vitals,
                $followUps,
                $pathways
            );
            $workflowSuggestions = $workflowAssistant->suggest($clinicalSummary);
        }

        $audit->handle('patient.360.viewed', $patient, [
            'doctor_id' => $doctor->id,
            'clinical_data_included' => $canViewClinical,
            'timeline_items' => $timeline->count(),
        ]);

        return Inertia::render('Portal/Patients/Patient360', [
            'doctor' => $doctor,
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->name,
                'phone' => $patient->phone,
                'national_code' => $patient->national_code,
                'created_at' => $patient->created_at?->toDateString(),
            ],
            'canViewClinical' => $canViewClinical,
            'summary' => $summary,
            'clinicalSummary' => $clinicalSummary,
            'workflowSuggestions' => $workflowSuggestions,
            'clinicalRecords' => $clinicalRecords->map(fn ($item) => [
                'id'=>$item->id,'kind'=>$item->kind,'title'=>$item->title,'description'=>$item->description,
                'dosage'=>$item->dosage,'frequency'=>$item->frequency,'start_date'=>$item->start_date?->format('Y-m-d'),
                'end_date'=>$item->end_date?->format('Y-m-d'),'is_active'=>$item->is_active,
            ])->values(),
            'problems' => $problems->map(fn ($item) => [
                'id'=>$item->id,'category'=>$item->category,'title'=>$item->title,'code'=>$item->code,
                'code_system'=>$item->code_system,'description'=>$item->description,'status'=>$item->status,
                'severity'=>$item->severity,'onset_date'=>$item->onset_date?->format('Y-m-d'),
                'resolved_on'=>$item->resolved_on?->format('Y-m-d'),'source'=>$item->source,
            ])->values(),
            'vitals' => $vitals->map(fn ($item) => [
                'id'=>$item->id,'type'=>$item->measurement_type,'value'=>$item->value,'unit'=>$item->unit,
                'measured_at'=>$item->measured_at?->toIso8601String(),'note'=>$item->note,
            ])->values(),
            'latestVitals' => $latestVitalByType->map(fn ($item) => [
                'type'=>$item->measurement_type,'value'=>$item->value,'unit'=>$item->unit,
                'measured_at'=>$item->measured_at?->toIso8601String(),
            ])->values(),
            'encounters' => $encounters->map(fn ($item) => [
                'id'=>$item->id,'date'=>$item->created_at?->toIso8601String(),'status'=>$item->status,
                'diagnosis'=>$item->diagnosis,'diagnosis_code'=>$item->diagnosis_code,
                'assessment'=>$item->assessment,'plan'=>$item->plan,'clinic'=>$item->clinic?->name,
                'prescriptions'=>$item->prescriptions->map(fn ($rx)=>[
                    'name'=>$rx->medication_name,'strength'=>$rx->strength,'dosage'=>$rx->dosage,
                    'route'=>$rx->route,'frequency'=>$rx->frequency,'duration_days'=>$rx->duration_days,
                ])->values(),
            ])->values(),
            'appointments' => $appointments->map(fn ($item) => [
                'id'=>$item->id,'date'=>$item->appointment_date ? Carbon::parse($item->appointment_date)->toDateString() : null,
                'time'=>$item->appointment_time,'status'=>$item->status,'clinic'=>$item->clinic?->name,
                'visit_number'=>$item->visit_number,
            ])->values(),
            'followUps' => $followUps->map(fn ($item) => [
                'id'=>$item->id,'title'=>$item->title,'description'=>$item->description,
                'due_at'=>$item->due_at?->toIso8601String(),'status'=>$item->status,'priority'=>$item->priority,
            ])->values(),
            'pathways' => $pathways->map(fn ($item) => [
                'id'=>$item->id,'title'=>$item->title,'goal'=>$item->goal,'status'=>$item->status,
                'steps_total'=>$item->steps->count(),'steps_verified'=>$item->steps->where('status','verified')->count(),
                'steps'=>$item->steps->map(fn ($step)=>['title'=>$step->title,'status'=>$step->status,'due_at'=>$step->due_at?->toIso8601String()])->values(),
            ])->values(),
            'medicationPlans' => $medicationPlans->map(fn ($item) => [
                'id'=>$item->id,'status'=>$item->status,'start_date'=>$item->start_date?->format('Y-m-d'),
                'end_date'=>$item->end_date?->format('Y-m-d'),
                'medication'=>$item->prescription?->medication_name,
                'strength'=>$item->prescription?->strength,'dosage'=>$item->prescription?->dosage,
                'frequency'=>$item->prescription?->frequency,
            ])->values(),
            'timeline' => $timeline,
            'dataMeta' => [
                'limited' => true,
                'appointments_loaded' => $appointments->count(),
                'encounters_loaded' => $encounters->count(),
                'vitals_loaded' => $vitals->count(),
                'timeline_limit' => 100,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function secretaryHasPermission(Doctor $doctor, string $permission): bool
    {
        $user = Auth::guard('web')->user();
        if (!$user || $user->role !== 'secretary') {
            return true;
        }

        $assignment = $user->doctors()->where('doctors.id', $doctor->id)->first();
        $permissions = $assignment?->pivot?->permissions ?? [];
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        return is_array($permissions) && in_array($permission, $permissions, true);
    }

    private function timelineItem(string $type, $date, string $title, ?string $description, string $status, int $id): array
    {
        $date = $date ? Carbon::parse($date) : null;
        return [
            'id' => $type.'-'.$id,
            'type' => $type,
            'date' => $date?->toIso8601String(),
            'sort_at' => $date?->toIso8601String(),
            'title' => $title,
            'description' => $description,
            'status' => $status,
        ];
    }

    private function recordLabel(string $kind): string
    {
        return match ($kind) {
            PatientClinicalRecord::KIND_ALLERGY => 'حساسیت ثبت‌شده',
            PatientClinicalRecord::KIND_MEDICATION => 'داروی ثبت‌شده',
            PatientClinicalRecord::KIND_HISTORY => 'سابقه پزشکی',
            PatientClinicalRecord::KIND_ADVERSE_REACTION => 'عارضه دارویی',
            default => 'سابقه بالینی',
        };
    }
}
