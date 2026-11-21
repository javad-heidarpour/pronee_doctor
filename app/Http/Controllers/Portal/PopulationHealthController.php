<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use App\Models\Patient;
use App\Models\PatientProblem;
use App\Models\PatientVitalSign;
use App\Services\PopulationHealth\PopulationHealthDisclosure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

final class PopulationHealthController extends Controller
{
    use HasActiveDoctor;

    public function index(
        Request $request,
        PopulationHealthDisclosure $disclosure,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.view', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        abort_if($clinicIds === [], 403, 'هیچ مطب فعالی برای بررسی در دسترس نیست.');

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'clinic_id' => ['nullable', 'integer'],
        ]);

        $to = isset($filters['to']) ? Carbon::parse($filters['to'])->toDateString() : today()->toDateString();
        $from = isset($filters['from']) ? Carbon::parse($filters['from'])->toDateString() : today()->subMonths(5)->startOfMonth()->toDateString();
        abort_if($to < $from, 422, 'تاریخ پایان نامعتبر است.');
        abort_if(Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 366, 422, 'بازه گزارش بیش از ۳۶۶ روز است.');

        $clinicId = isset($filters['clinic_id']) ? (int) $filters['clinic_id'] : null;
        if ($clinicId !== null) {
            $this->authorizeClinicAccess($clinicId, $doctor);
        }
        $scope = $clinicId !== null ? [$clinicId] : $clinicIds;

        $clinics = Address::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('id', $clinicIds)
            ->where('is_active', true)
            ->when($clinicId !== null, fn ($query) => $query->whereKey($clinicId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $appointments = DB::table('appointments')
            ->where('doctor_id', $doctor->id)
            ->whereIn('clinic_id', $scope)
            ->whereBetween('appointment_date', [$from, $to])
            ->whereNotNull('patient_id');

        $patientIds = (clone $appointments)->distinct()->pluck('patient_id');
        $cohortSize = $patientIds->count();
        $canDisclose = $disclosure->canDisclose($cohortSize);

        $monthlyRows = (clone $appointments)
            ->selectRaw("DATE_FORMAT(appointment_date, '%Y-%m') as period")
            ->selectRaw('COUNT(*) as appointment_count')
            ->selectRaw('COUNT(DISTINCT patient_id) as patient_count')
            ->selectRaw("SUM(CASE WHEN status IN ('visited','completed') THEN 1 ELSE 0 END) as completed_count")
            ->selectRaw("SUM(CASE WHEN status IN ('cancelled','canceled') THEN 1 ELSE 0 END) as cancelled_count")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $monthly = $monthlyRows->map(function ($row) use ($disclosure): array {
            $patients = (int) $row->patient_count;
            if (! $disclosure->canDisclose($patients)) {
                return ['period' => (string) $row->period, 'suppressed' => true];
            }

            return [
                'period' => (string) $row->period,
                'suppressed' => false,
                'patient_count' => $patients,
                'appointment_count' => (int) $row->appointment_count,
                'completed_count' => (int) $row->completed_count,
                'cancelled_count' => (int) $row->cancelled_count,
            ];
        })->values();

        $problemRows = collect();
        $vitalRows = collect();
        if ($canDisclose && $patientIds->isNotEmpty()) {
            $problemRows = PatientProblem::query()
                ->where('doctor_id', $doctor->id)
                ->whereIn('patient_id', $patientIds)
                ->where('status', PatientProblem::STATUS_ACTIVE)
                ->select('category')
                ->selectRaw('COUNT(DISTINCT patient_id) as patient_count')
                ->groupBy('category')
                ->orderBy('category')
                ->get()
                ->filter(fn ($row) => $disclosure->canDisclose((int) $row->patient_count))
                ->map(fn ($row) => [
                    'category' => (string) $row->category,
                    'patient_count' => (int) $row->patient_count,
                ])->values();

            $vitalRows = PatientVitalSign::query()
                ->where('doctor_id', $doctor->id)
                ->whereIn('patient_id', $patientIds)
                ->whereBetween('measured_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()])
                ->select('measurement_type', 'unit')
                ->selectRaw('COUNT(DISTINCT patient_id) as patient_count')
                ->selectRaw('COUNT(*) as measurement_count')
                ->groupBy('measurement_type', 'unit')
                ->orderBy('measurement_type')
                ->get()
                ->filter(fn ($row) => $disclosure->canDisclose((int) $row->patient_count))
                ->map(fn ($row) => [
                    'measurement_type' => (string) $row->measurement_type,
                    'unit' => (string) $row->unit,
                    'patient_count' => (int) $row->patient_count,
                    'measurement_count' => (int) $row->measurement_count,
                ])->values();
        }

        $summary = $canDisclose ? [
            'patients' => $cohortSize,
            'appointments' => (int) (clone $appointments)->count(),
            'completed' => (int) (clone $appointments)->whereIn('status', ['visited', 'completed'])->count(),
            'cancelled' => (int) (clone $appointments)->whereIn('status', ['cancelled', 'canceled'])->count(),
            'active_problem_categories' => $problemRows->count(),
            'vital_measurement_types' => $vitalRows->count(),
        ] : null;

        $audit->handle('population_health.trends_viewed', null, [
            'doctor_id' => $doctor->id,
            'clinic_count' => count($scope),
            'cohort_size_band' => $canDisclose ? 'disclosed' : 'suppressed',
            'period_days' => Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1,
        ]);

        return Inertia::render('Portal/PopulationHealth/Index', [
            'doctor' => $doctor,
            'clinics' => $clinics,
            'filters' => ['from' => $from, 'to' => $to, 'clinic_id' => $clinicId],
            'disclosure' => ['minimum_cohort_size' => PopulationHealthDisclosure::MINIMUM_COHORT_SIZE, 'suppressed' => ! $canDisclose],
            'summary' => $summary,
            'monthly' => $monthly,
            'problems' => $problemRows,
            'vitals' => $vitalRows,
            'generatedAt' => now()->toIso8601String(),
            'definitions' => [
                'cohort' => 'بیمارانی که حداقل یک نوبت دارای شناسه بیمار در شعبه و بازه انتخابی داشته‌اند.',
                'completed' => 'نوبت با وضعیت visited یا completed.',
                'problems' => 'مشکلات فعال ثبت‌شده؛ دسته‌بندی‌ها تشخیص جدید یا شیوع جمعیتی محسوب نمی‌شوند.',
                'vitals' => 'تعداد ثبت اندازه‌گیری به تفکیک نوع و واحد؛ هیچ مقدار آستانه‌ای یا وضعیت طبیعی/غیرطبیعی استنباط نمی‌شود.',
            ],
        ]);
    }
}
