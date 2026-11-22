<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PatientAccountAccess;
use App\Models\PatientEncounter;
use App\Models\PatientEncounterPrescription;
use App\Models\PatientMedicationDose;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\PatientMedicationPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PatientMedicationController extends Controller
{
    private function owner(?int $patientId = null, ?int $accessId = null): array
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && $user->role === 'user' && $user->is_active, 403);

        $query = PatientAccountAccess::query()->active()
            ->where('user_id', $user->id)
            ->where('access_type', 'owner');

        if ($patientId !== null) {
            $query->where('patient_id', $patientId);
        }

        if ($accessId !== null) {
            $query->whereKey($accessId);
        }

        return [$user, $query->firstOrFail()];
    }

    public function store(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $data = $request->validate([
            'access_id' => ['required', 'integer', 'min:1'],
            'prescription_id' => ['required', 'integer', 'exists:patient_encounter_prescriptions,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addYear()->toDateString()],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'before_or_equal:'.now()->addYear()->toDateString()],
            'reminder_times' => ['required', 'array', 'min:1', 'max:6'],
            'reminder_times.*' => ['required', 'date_format:H:i', 'distinct'],
        ]);
        [$user, $access] = $this->owner(accessId: (int) $data['access_id']);

        $prescription = PatientEncounterPrescription::query()
            ->whereKey($data['prescription_id'])
            ->whereHas('encounter', fn ($q) => $q->where('patient_id', $access->patient_id)->where('status', PatientEncounter::STATUS_SIGNED))
            ->firstOrFail();

        $endDate = $data['end_date'] ?? ($prescription->duration_days
            ? Carbon::parse($data['start_date'])->addDays(max(0, (int) $prescription->duration_days - 1))->toDateString()
            : null);

        $plan = PatientMedicationPlan::query()->updateOrCreate(
            ['user_id' => $user->id, 'prescription_id' => $prescription->id],
            [
                'patient_id' => $access->patient_id,
                'start_date' => $data['start_date'],
                'end_date' => $endDate,
                'reminder_times' => array_values($data['reminder_times']),
                'timezone' => config('app.timezone', 'Asia/Tehran'),
                'status' => 'active',
            ]
        );

        $audit->handle('patient.medication.reminder_created', $plan);

        return back()->with('success', 'برنامه یادآوری شخصی دارو ذخیره شد. این برنامه نسخه پزشک را تغییر نمی‌دهد.');
    }

    public function recordDose(Request $request, PatientMedicationPlan $plan, RecordSecurityAuditEventAction $audit)
    {
        [$user, $access] = $this->owner(patientId: (int) $plan->patient_id);
        abort_unless((int) $plan->user_id === (int) $user->id && $plan->status === 'active', 404);

        $data = $request->validate([
            'scheduled_for' => ['required', 'date'],
            'status' => ['required', 'in:taken,skipped'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($plan, $user, $access, $data): void {
            // Lock the owner grant before the plan so dose recording and pausing
            // serialize against each other and cannot use a revoked grant.
            $lockedAccess = PatientAccountAccess::query()
                ->active()
                ->whereKey($access->id)
                ->where('user_id', $user->id)
                ->where('patient_id', $plan->patient_id)
                ->where('access_type', 'owner')
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPlan = PatientMedicationPlan::query()
                ->whereKey($plan->id)
                ->lockForUpdate()
                ->firstOrFail();

            // The route-bound model may have been loaded before another request
            // paused the plan. Always authorize against the freshly locked row.
            abort_unless(
                (int) $lockedPlan->patient_id === (int) $lockedAccess->patient_id &&
                (int) $lockedPlan->user_id === (int) $user->id &&
                $lockedPlan->status === 'active',
                404
            );

            $scheduled = Carbon::parse($data['scheduled_for'], $lockedPlan->timezone);
            $date = $scheduled->toDateString();
            abort_unless($date >= $lockedPlan->start_date->toDateString(), 422);
            abort_if($lockedPlan->end_date && $date > $lockedPlan->end_date->toDateString(), 422);
            abort_unless(in_array($scheduled->format('H:i'), $lockedPlan->reminder_times ?? [], true), 422);

            if ($scheduled->gt(now($lockedPlan->timezone)->addMinutes(15)) || $scheduled->lt(now($lockedPlan->timezone)->subDays(7))) {
                throw ValidationException::withMessages(['scheduled_for' => 'ثبت مصرف فقط برای نوبت‌های هفت روز گذشته تا ۱۵ دقیقه آینده مجاز است.']);
            }

            PatientMedicationDose::query()->updateOrCreate(
                ['plan_id' => $lockedPlan->id, 'scheduled_for' => $scheduled->utc()],
                [
                    'status' => $data['status'],
                    'recorded_by_user_id' => $user->id,
                    'recorded_at' => now(),
                    'note' => $data['note'] ?? null,
                ]
            );
        });

        $audit->handle('patient.medication.dose_recorded', $plan);

        return back()->with('success', $data['status'] === 'taken' ? 'مصرف دارو ثبت شد.' : 'عدم مصرف این نوبت ثبت شد.');
    }

    public function pause(Request $request, PatientMedicationPlan $plan, RecordSecurityAuditEventAction $audit)
    {
        [$user, $access] = $this->owner(patientId: (int) $plan->patient_id);
        abort_unless((int) $plan->user_id === (int) $user->id, 404);

        DB::transaction(function () use ($plan, $user, $access): void {
            $lockedAccess = PatientAccountAccess::query()
                ->active()
                ->whereKey($access->id)
                ->where('user_id', $user->id)
                ->where('patient_id', $plan->patient_id)
                ->where('access_type', 'owner')
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPlan = PatientMedicationPlan::query()
                ->whereKey($plan->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                (int) $lockedPlan->patient_id === (int) $lockedAccess->patient_id &&
                (int) $lockedPlan->user_id === (int) $user->id,
                404
            );

            $lockedPlan->update(['status' => 'paused']);
        });

        $plan->refresh();
        $audit->handle('patient.medication.reminder_paused', $plan);

        return back()->with('success', 'یادآوری شخصی متوقف شد؛ این کار نسخه یا دستور پزشک را تغییر نمی‌دهد.');
    }
}
