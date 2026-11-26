<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use App\Models\ClinicInventoryItem;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Query\Builder as QueryBuilder;
use App\Services\ClinicIntelligence\InventoryDemandForecaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class ClinicIntelligenceController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request, InventoryDemandForecaster $forecaster, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $user = Auth::guard('web')->user();
        abort_unless(in_array($user?->role, ['doctor', 'secretary'], true), 403);
        $clinicIds = $this->accessibleClinicIds($doctor);
        $isSecretary = $user->role === 'secretary';
        $permissions = $this->secretaryPermissions($doctor);
        $canInventory = !$isSecretary || in_array('inventory.view', $permissions, true) || in_array('inventory.manage', $permissions, true);
        $canReadPatients = in_array('patients.view', $permissions, true) || in_array('patients.manage', $permissions, true);
        $canReadClinical = in_array('patients.clinical.view', $permissions, true) || in_array('patients.clinical.manage', $permissions, true);
        $canHealth = !$isSecretary || ($canReadPatients && $canReadClinical);
        $canManageInventory = !$isSecretary || in_array('inventory.manage', $permissions, true);
        $canManageHealth = !$isSecretary || (in_array('patients.manage', $permissions, true) && in_array('patients.clinical.manage', $permissions, true));
        abort_unless($canInventory || $canHealth, 403);

        $forecast = $canInventory ? $forecaster->forecast($doctor->id, $clinicIds, $request->integer('horizon', 30)) : [
            'horizon_days' => 30, 'items' => [], 'summary' => ['total' => 0, 'critical' => 0, 'warning' => 0, 'insufficient_data' => 0],
        ];

        if ($canHealth) {
            $programQuery = DB::table('clinic_health_programs')->where('doctor_id', $doctor->id)->where('is_active', true);
            if ($isSecretary) {
                // Doctor-wide programs cannot be safely attributed to a secretary's clinic assignment.
                $programQuery->whereIn('clinic_id', $clinicIds);
            } else {
                $programQuery->where(fn ($q) => $q->whereNull('clinic_id')->orWhereIn('clinic_id', $clinicIds));
            }
            $programs = $programQuery->orderBy('title')->get();

            $membershipQuery = DB::table('clinic_health_memberships as m')
                ->join('patients as p', 'p.id', '=', 'm.patient_id')
                ->join('clinic_health_programs as g', 'g.id', '=', 'm.program_id')
                ->where('m.status', 'active');
            $this->applyMembershipClinicScope($membershipQuery, $doctor, $clinicIds, $isSecretary);
            $memberships = $membershipQuery
                ->select('m.id', 'm.patient_id', 'm.program_id', 'm.enrolled_at', 'm.last_checkup_at', 'm.consent_at',
                    'p.name as patient_name', 'p.phone', 'g.title as program_title', 'g.interval_days')
                ->selectSub(DB::table('clinic_health_points')->selectRaw('COALESCE(SUM(points),0)')
                    ->whereColumn('membership_id', 'm.id')->whereColumn('doctor_id', 'm.doctor_id')
                    ->whereColumn('patient_id', 'm.patient_id'), 'points')
                ->orderByDesc('m.enrolled_at')->limit(100)->get();
        } else {
            $programs = collect();
            $memberships = collect();
        }

        $reminders = $memberships->map(function ($membership) {
            $due = \Illuminate\Support\Carbon::parse($membership->last_checkup_at ?: $membership->enrolled_at)->addDays((int) $membership->interval_days);
            return [
                'membership_id' => (int) $membership->id, 'patient_name' => $membership->patient_name,
                'phone' => $membership->phone, 'program_title' => $membership->program_title,
                'due_date' => $due->toDateString(), 'overdue' => $due->toDateString() < today()->toDateString(),
                'points' => (int) $membership->points,
            ];
        })->sortBy('due_date')->values();

        $patients = $canHealth ? Patient::query()->where('doctor_id', $doctor->id)
            ->whereHas('appointments', fn ($q) => $q->whereIn('clinic_id', $clinicIds)->whereNotIn('status', ['cancelled', 'failed', 'rejected']))
            ->orderBy('name')->get(['id', 'name', 'phone']) : collect();

        if ($user->role === 'doctor') {
            $scenarios = DB::table('clinic_economic_scenarios')
                ->where('doctor_id', $doctor->id)->latest()->limit(10)->get();

            $appointments = DB::table('appointments')->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $clinicIds)->whereBetween('appointment_date', [now()->subDays(90)->toDateString(), today()->toDateString()])
                ->whereNotIn('status', ['cancelled', 'rejected']);
            $appointmentCount = (clone $appointments)->count();
            $paidPayments = DB::table('appointment_payments as payments')
                ->join('appointments as a', 'a.id', '=', 'payments.appointment_id')
                ->where('a.doctor_id', $doctor->id)->whereIn('a.clinic_id', $clinicIds)
                ->where('payments.status', 'paid')->whereNotNull('payments.paid_at')
                ->where('payments.paid_at', '>=', now()->subDays(90));
            $revenue = (int) (clone $paidPayments)->sum('payments.amount_rial');
            $paidAppointmentCount = (int) (clone $paidPayments)->distinct('a.id')->count('a.id');
            $expenses = (int) DB::table('clinic_expenses')->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $clinicIds)->whereDate('expense_date', '>=', now()->subDays(90)->toDateString())->sum('amount');
            $ownedOrganizationIds = DB::table('clinic_organizations')
                ->where('owner_user_id', $user->id)->where('status', 'active')->pluck('id');
            $paidPayroll = (int) DB::table('clinic_staff_payrolls')
                ->whereIn('organization_id', $ownedOrganizationIds)->where('status', 'paid')
                ->whereDate('period_end', '>=', now()->subDays(90)->toDateString())->sum('net_amount');
            $baseline = [
                'appointments_90d' => $appointmentCount,
                'monthly_appointments' => round($appointmentCount / 3, 1),
                'monthly_revenue' => (int) round($revenue / 3),
                'monthly_expenses' => (int) round(($expenses + $paidPayroll) / 3),
                'average_fee' => $paidAppointmentCount > 0 ? (int) round($revenue / $paidAppointmentCount) : 0,
            ];
        } else {
            $scenarios = collect();
            $baseline = [
                'appointments_90d' => 0, 'monthly_appointments' => 0,
                'monthly_revenue' => 0, 'monthly_expenses' => 0, 'average_fee' => 0,
            ];
        }

        $audit->handle('clinic.intelligence.viewed', null, [
            'doctor_id' => $doctor->id, 'clinic_count' => count($clinicIds),
        ]);

        return Inertia::render('Portal/ClinicIntelligence/Index', [
            'doctor' => $doctor,
            'clinics' => Address::query()->whereIn('id', $clinicIds)->get(['id', 'name']),
            'patients' => $patients,
            'forecast' => $forecast,
            'programs' => $programs,
            'memberships' => $memberships,
            'reminders' => $reminders,
            'scenarios' => $scenarios,
            'permissions' => [
                'inventory' => $canInventory, 'inventory_manage' => $canManageInventory,
                'health' => $canHealth, 'health_manage' => $canManageHealth,
                'simulator' => $user->role === 'doctor',
            ],
            'baseline' => $baseline,
        ]);
    }

    public function updateInventorySettings(Request $request, int $item)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('inventory.manage', $doctor);
        $data = $request->validate([
            'lead_time_days' => ['required', 'integer', 'min:1', 'max:180'],
            'safety_stock_days' => ['required', 'integer', 'min:0', 'max:180'],
            'preferred_supplier' => ['nullable', 'string', 'max:180'],
        ]);
        $row = ClinicInventoryItem::query()->whereKey($item)
            ->whereIn('clinic_id', $this->accessibleClinicIds($doctor))->firstOrFail();
        $row->update($data);
        return back()->with('success', 'زمان تأمین و ذخیره احتیاطی کالا ذخیره شد.');
    }

    public function storeProgram(Request $request)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $data = $request->validate([
            'clinic_id' => ['nullable', 'integer', Rule::in($this->accessibleClinicIds($doctor))],
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', Rule::in(['general_checkup', 'diabetes', 'cardiovascular', 'dental', 'vaccination', 'maternal', 'other'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'interval_days' => ['required', 'integer', 'min:30', 'max:1095'],
        ]);
        DB::table('clinic_health_programs')->insert($data + [
            'doctor_id' => $doctor->id, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return back()->with('success', 'برنامه پیشگیری سلامت ایجاد شد.');
    }

    public function enrollPatient(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeHealthManagement($doctor);
        $data = $request->validate([
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')->where('doctor_id', $doctor->id)],
            'program_id' => ['required', 'integer', Rule::exists('clinic_health_programs', 'id')->where('doctor_id', $doctor->id)],
            'consent' => ['accepted'],
        ]);

        $clinicIds = $this->accessibleClinicIds($doctor);
        $isSecretary = Auth::guard('web')->user()?->role === 'secretary';
        $userId = Auth::guard('web')->id();

        DB::transaction(function () use ($doctor, $data, $clinicIds, $isSecretary, $userId): void {
            $program = DB::table('clinic_health_programs')->where('id', $data['program_id'])
                ->where('doctor_id', $doctor->id)->where('is_active', true)->lockForUpdate()->first();
            abort_unless($program, 404);

            $programClinicId = $program->clinic_id === null ? null : (int) $program->clinic_id;
            if ($isSecretary) {
                abort_unless($programClinicId !== null && in_array($programClinicId, $clinicIds, true), 404);
            }
            if ($programClinicId !== null) {
                abort_unless(in_array($programClinicId, $clinicIds, true), 404);
                Address::query()->whereKey($programClinicId)->where('doctor_id', $doctor->id)
                    ->where('is_active', true)->lockForUpdate()->firstOrFail();
            }

            $patient = Patient::query()->whereKey($data['patient_id'])->where('doctor_id', $doctor->id)
                ->lockForUpdate()->firstOrFail();
            $appointmentQuery = DB::table('appointments')->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)->whereNotIn('status', ['cancelled', 'failed', 'rejected']);
            if ($programClinicId !== null) {
                $appointmentQuery->where('clinic_id', $programClinicId);
            } else {
                $appointmentQuery->whereIn('clinic_id', $clinicIds);
            }
            abort_unless($appointmentQuery->exists(), 422, 'این بیمار در مطب مربوط به برنامه سابقه مراجعه معتبر ندارد.');

            $membership = DB::table('clinic_health_memberships')->where('patient_id', $patient->id)
                ->where('program_id', $program->id)->lockForUpdate()->first();
            $values = [
                'doctor_id' => $doctor->id, 'status' => 'active', 'consent_at' => now(),
                'consent_recorded_by' => $userId, 'enrolled_at' => now(), 'updated_at' => now(),
            ];
            if ($membership) {
                DB::table('clinic_health_memberships')->where('id', $membership->id)->update($values);
            } else {
                DB::table('clinic_health_memberships')->insert($values + [
                    'patient_id' => $patient->id, 'program_id' => $program->id, 'created_at' => now(),
                ]);
            }
        }, 3);

        $audit->handle('clinic.health_membership.enrolled', null, [
            'doctor_id' => $doctor->id, 'clinic_count' => count($clinicIds),
        ]);

        return back()->with('success', 'عضویت داوطلبانه بیمار با ثبت رضایت ایجاد شد.');
    }

    public function recordPreventiveCheckup(int $membership, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeHealthManagement($doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        $isSecretary = Auth::guard('web')->user()?->role === 'secretary';
        $updated = DB::transaction(function () use ($membership, $doctor, $clinicIds, $isSecretary): int {
            $row = $this->accessibleMembershipQuery($membership, $doctor, $clinicIds, $isSecretary)
                ->where('m.status', 'active')->lockForUpdate()->first();
            abort_unless($row && $row->consent_at, 404);
            return DB::table('clinic_health_memberships')->where('id', $membership)
                ->where('doctor_id', $doctor->id)->where('status', 'active')
                ->update(['last_checkup_at' => now(), 'updated_at' => now()]);
        }, 3);
        abort_unless($updated === 1, 404);
        $audit->handle('clinic.health_membership.checkup_recorded', null, [
            'doctor_id' => $doctor->id, 'status_changed' => true,
        ]);
        return back()->with('success', 'تاریخ آخرین چکاپ ثبت شد و موعد بعدی محاسبه می‌شود.');
    }

    public function withdrawMembership(int $membership, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeHealthManagement($doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        $isSecretary = Auth::guard('web')->user()?->role === 'secretary';
        $updated = DB::transaction(function () use ($membership, $doctor, $clinicIds, $isSecretary): int {
            $row = $this->accessibleMembershipQuery($membership, $doctor, $clinicIds, $isSecretary)
                ->where('m.status', 'active')->lockForUpdate()->first();
            abort_unless($row && $row->consent_at, 404);
            return DB::table('clinic_health_memberships')->where('id', $membership)
                ->where('doctor_id', $doctor->id)->where('status', 'active')
                ->update(['status' => 'withdrawn', 'updated_at' => now()]);
        }, 3);
        abort_unless($updated === 1, 404);
        $audit->handle('clinic.health_membership.withdrawn', null, [
            'doctor_id' => $doctor->id, 'status_changed' => true,
        ]);
        return back()->with('success', 'عضویت و یادآوری‌های این برنامه متوقف شد.');
    }

    public function awardWellnessPoints(Request $request, int $membership, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeHealthManagement($doctor);
        $data = $request->validate([
            'activity_key' => ['required', Rule::in(['education_completed', 'self_care_goal_completed', 'preventive_checklist_completed'])],
            'description' => ['required', 'string', 'max:180'],
            'source_key' => ['required', 'string', 'max:120'],
        ]);
        $clinicIds = $this->accessibleClinicIds($doctor);
        $isSecretary = Auth::guard('web')->user()?->role === 'secretary';

        DB::transaction(function () use ($doctor, $membership, $data, $clinicIds, $isSecretary): void {
            $row = $this->accessibleMembershipQuery($membership, $doctor, $clinicIds, $isSecretary)
                ->where('m.status', 'active')->lockForUpdate()->first();
            abort_unless($row && $row->consent_at, 404);
            $points = match ($data['activity_key']) {
                'education_completed' => 5,
                'self_care_goal_completed' => 10,
                'preventive_checklist_completed' => 5,
            };
            $alreadyToday = DB::table('clinic_health_points')->where('membership_id', $membership)
                ->where('doctor_id', $doctor->id)->where('patient_id', $row->patient_id)
                ->whereDate('created_at', today())->sum('points');
            abort_if($alreadyToday + $points > 20, 422, 'سقف امتیاز روزانه رعایت شده است.');
            DB::table('clinic_health_points')->insertOrIgnore([
                'membership_id' => $membership, 'doctor_id' => $doctor->id,
                'patient_id' => $row->patient_id, 'points' => $points,
                'activity_key' => $data['activity_key'], 'description' => $data['description'],
                'source_key' => $data['source_key'], 'awarded_by' => Auth::guard('web')->id(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);

        $audit->handle('clinic.health_membership.points_awarded', null, [
            'doctor_id' => $doctor->id, 'status_changed' => true,
        ]);

        return back()->with('success', 'امتیاز فعالیت آموزشی/پیشگیرانه ثبت شد.');
    }

    public function simulateEconomics(Request $request)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $data = $request->validate([
            'clinic_id' => ['nullable', 'integer', Rule::in($this->accessibleClinicIds($doctor))],
            'scenario_type' => ['required', Rule::in(['hire_doctor', 'new_branch', 'equipment', 'fee_change'])],
            'name' => ['required', 'string', 'max:180'],
            'extra_appointments_month' => ['required', 'numeric', 'min:0', 'max:100000'],
            'average_fee' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'monthly_fixed_cost' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'one_time_cost' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'fee_change_percent' => ['nullable', 'numeric', 'min:-50', 'max:100'],
            'volume_change_percent' => ['nullable', 'numeric', 'min:-80', 'max:200'],
            'capacity_monthly' => ['required', 'numeric', 'min:0', 'max:100000'],
        ]);

        $last90 = DB::table('appointments')->where('doctor_id', $doctor->id)
            ->whereBetween('appointment_date', [now()->subDays(90)->toDateString(), today()->toDateString()])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->when($data['clinic_id'] ?? null, fn ($q, $clinicId) => $q->where('clinic_id', $clinicId));
        $baselineCount = (int) (clone $last90)->count();
        $paidPayments = DB::table('appointment_payments as payments')
            ->join('appointments as a', 'a.id', '=', 'payments.appointment_id')
            ->where('a.doctor_id', $doctor->id)->where('payments.status', 'paid')
            ->whereNotNull('payments.paid_at')->where('payments.paid_at', '>=', now()->subDays(90))
            ->when($data['clinic_id'] ?? null, fn ($q, $clinicId) => $q->where('a.clinic_id', $clinicId));
        $baselineRevenue = (int) (clone $paidPayments)->sum('payments.amount_rial') / 3;
        $paidAppointmentCount = (int) (clone $paidPayments)->distinct('a.id')->count('a.id');
        $expenseQuery = DB::table('clinic_expenses')->where('doctor_id', $doctor->id)
            ->whereDate('expense_date', '>=', now()->subDays(90)->toDateString())
            ->when($data['clinic_id'] ?? null, fn ($q, $clinicId) => $q->where('clinic_id', $clinicId));
        $baselineExpenses = (int) (clone $expenseQuery)->sum('amount') / 3;
        $organizationIds = DB::table('clinic_organizations')->where('owner_user_id', Auth::guard('web')->id())
            ->where('status', 'active')->pluck('id');
        $payrollQuery = DB::table('clinic_staff_payrolls')->whereIn('organization_id', $organizationIds)
            ->where('status', 'paid')->whereDate('period_end', '>=', now()->subDays(90)->toDateString())
            ->when($data['clinic_id'] ?? null, fn ($q, $clinicId) => $q->where(fn ($scope) => $scope->whereNull('clinic_id')->orWhere('clinic_id', $clinicId)));
        $baselineExpenses += (int) (clone $payrollQuery)->sum('net_amount') / 3;
        $averageFee = (int) ($data['average_fee'] ?: ($paidAppointmentCount > 0 ? round(($baselineRevenue * 3) / $paidAppointmentCount) : 0));
        $extraAppointments = min((float) $data['extra_appointments_month'], (float) $data['capacity_monthly']);
        $volumeFactor = 1 + ((float) ($data['volume_change_percent'] ?? 0) / 100);
        $feeFactor = 1 + ((float) ($data['fee_change_percent'] ?? 0) / 100);
        $incrementalRevenue = (int) round($extraAppointments * $averageFee * $feeFactor * $volumeFactor);
        if ($data['scenario_type'] === 'fee_change') {
            $incrementalRevenue = (int) round($baselineRevenue * ($feeFactor * $volumeFactor - 1));
        }
        $monthlyNetImpact = $incrementalRevenue - (int) $data['monthly_fixed_cost'];
        $yearOneImpact = ($monthlyNetImpact * 12) - (int) $data['one_time_cost'];
        $breakEvenMonths = $monthlyNetImpact > 0 ? (int) ceil(((int) $data['one_time_cost']) / $monthlyNetImpact) : null;
        $capacityUse = (float) $data['capacity_monthly'] > 0
            ? round(($extraAppointments / (float) $data['capacity_monthly']) * 100, 1) : 0;

        $results = [
            'baseline_monthly_revenue' => (int) round($baselineRevenue),
            'baseline_monthly_expenses' => (int) round($baselineExpenses),
            'baseline_monthly_net' => (int) round($baselineRevenue - $baselineExpenses),
            'incremental_monthly_revenue' => $incrementalRevenue,
            'incremental_monthly_cost' => (int) $data['monthly_fixed_cost'],
            'monthly_net_impact' => $monthlyNetImpact,
            'one_time_cost' => (int) $data['one_time_cost'],
            'year_one_impact' => $yearOneImpact,
            'break_even_months' => $breakEvenMonths,
            'capacity_utilization_percent' => $capacityUse,
            'capacity_remaining' => max(0, (float) $data['capacity_monthly'] - $extraAppointments),
            'assumption' => 'برآورد سناریویی بر اساس داده ۹۰ روز اخیر و ورودی‌های مدیر؛ تضمین درآمد یا توصیه قطعی سرمایه‌گذاری نیست.',
        ];

        DB::table('clinic_economic_scenarios')->insert([
            'doctor_id' => $doctor->id, 'clinic_id' => $data['clinic_id'] ?? null,
            'created_by' => Auth::guard('web')->id(), 'scenario_type' => $data['scenario_type'],
            'name' => $data['name'], 'inputs' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'results' => json_encode($results, JSON_UNESCAPED_UNICODE),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'سناریوی اقتصادی محاسبه و ذخیره شد.');
    }

    private function authorizeHealthManagement(Doctor $doctor): void
    {
        $this->authorizeSecretaryPermission('patients.manage', $doctor);
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
    }

    private function accessibleMembershipQuery(int $membership, Doctor $doctor, array $clinicIds, bool $isSecretary): QueryBuilder
    {
        $query = DB::table('clinic_health_memberships as m')
            ->join('patients as p', 'p.id', '=', 'm.patient_id')
            ->join('clinic_health_programs as g', 'g.id', '=', 'm.program_id')
            ->where('m.id', $membership);
        $this->applyMembershipClinicScope($query, $doctor, $clinicIds, $isSecretary);

        return $query->select('m.*');
    }

    private function applyMembershipClinicScope(QueryBuilder $query, Doctor $doctor, array $clinicIds, bool $isSecretary): QueryBuilder
    {
        $query->where('m.doctor_id', $doctor->id)
            ->where('p.doctor_id', $doctor->id)
            ->where('g.doctor_id', $doctor->id);

        if ($isSecretary) {
            return $query->whereIn('g.clinic_id', $clinicIds)
                ->whereExists(function (QueryBuilder $appointments): void {
                    $appointments->selectRaw('1')->from('appointments as a')
                        ->whereColumn('a.doctor_id', 'm.doctor_id')
                        ->whereColumn('a.patient_id', 'm.patient_id')
                        ->whereColumn('a.clinic_id', 'g.clinic_id')
                        ->whereNotIn('a.status', ['cancelled', 'failed', 'rejected']);
                });
        }

        return $query->where(function (QueryBuilder $scope) use ($clinicIds): void {
            $scope->whereNull('g.clinic_id');
            if ($clinicIds !== []) {
                $scope->orWhere(function (QueryBuilder $clinicScope) use ($clinicIds): void {
                    $clinicScope->whereIn('g.clinic_id', $clinicIds)
                        ->whereExists(function (QueryBuilder $appointments): void {
                            $appointments->selectRaw('1')->from('appointments as a')
                                ->whereColumn('a.doctor_id', 'm.doctor_id')
                                ->whereColumn('a.patient_id', 'm.patient_id')
                                ->whereColumn('a.clinic_id', 'g.clinic_id')
                                ->whereNotIn('a.status', ['cancelled', 'failed', 'rejected']);
                        });
                });
            }
        });
    }

    private function secretaryPermissions($doctor): array
    {
        $user = Auth::guard('web')->user();
        if ($user?->role !== 'secretary') return [];
        $assignment = $user->doctors()->where('doctors.id', $doctor->id)->first();
        $permissions = $assignment?->pivot?->permissions ?? [];
        if (is_string($permissions)) $permissions = json_decode($permissions, true) ?: [];
        return is_array($permissions) ? $permissions : [];
    }
}
