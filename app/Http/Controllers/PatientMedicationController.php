<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PatientAccountAccess;
use App\Models\PatientEncounter;
use App\Models\PatientEncounterPrescription;
use App\Models\PatientMedicationDose;
use App\Models\PatientMedicationPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PatientMedicationController extends Controller
{
    private function owner(): array
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && $user->role === 'user', 403);
        $access = PatientAccountAccess::query()->active()
            ->where('user_id', $user->id)->where('access_type', 'owner')->firstOrFail();
        return [$user, $access];
    }

    public function store(Request $request)
    {
        [$user, $access] = $this->owner();
        $data = $request->validate([
            'prescription_id' => ['required', 'integer', 'exists:patient_encounter_prescriptions,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addYear()->toDateString()],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'before_or_equal:'.now()->addYear()->toDateString()],
            'reminder_times' => ['required', 'array', 'min:1', 'max:6'],
            'reminder_times.*' => ['required', 'date_format:H:i', 'distinct'],
        ]);

        $prescription = PatientEncounterPrescription::query()
            ->whereKey($data['prescription_id'])
            ->whereHas('encounter', fn ($q) => $q->where('patient_id', $access->patient_id)->where('status', PatientEncounter::STATUS_SIGNED))
            ->firstOrFail();

        $endDate = $data['end_date'] ?? ($prescription->duration_days
            ? Carbon::parse($data['start_date'])->addDays(max(0, (int) $prescription->duration_days - 1))->toDateString()
            : null);

        PatientMedicationPlan::query()->updateOrCreate(
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

        return back()->with('success', 'برنامه یادآوری شخصی دارو ذخیره شد. این برنامه نسخه پزشک را تغییر نمی‌دهد.');
    }

    public function recordDose(Request $request, PatientMedicationPlan $plan)
    {
        [$user, $access] = $this->owner();
        abort_unless((int) $plan->patient_id === (int) $access->patient_id && (int) $plan->user_id === (int) $user->id && $plan->status === 'active', 404);

        $data = $request->validate([
            'scheduled_for' => ['required', 'date'],
            'status' => ['required', 'in:taken,skipped'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $scheduled = Carbon::parse($data['scheduled_for'])->timezone($plan->timezone);
        $date = $scheduled->toDateString();
        abort_unless($date >= $plan->start_date->toDateString(), 422);
        abort_if($plan->end_date && $date > $plan->end_date->toDateString(), 422);
        abort_unless(in_array($scheduled->format('H:i'), $plan->reminder_times ?? [], true), 422);

        if ($scheduled->gt(now($plan->timezone)->addMinutes(15)) || $scheduled->lt(now($plan->timezone)->subDays(7))) {
            throw ValidationException::withMessages(['scheduled_for' => 'ثبت مصرف فقط برای نوبت‌های هفت روز گذشته تا ۱۵ دقیقه آینده مجاز است.']);
        }

        DB::transaction(function () use ($plan, $user, $data, $scheduled): void {
            PatientMedicationPlan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();
            PatientMedicationDose::query()->updateOrCreate(
                ['plan_id' => $plan->id, 'scheduled_for' => $scheduled->utc()],
                [
                    'status' => $data['status'],
                    'recorded_by_user_id' => $user->id,
                    'recorded_at' => now(),
                    'note' => $data['note'] ?? null,
                ]
            );
        });

        return back()->with('success', $data['status'] === 'taken' ? 'مصرف دارو ثبت شد.' : 'عدم مصرف این نوبت ثبت شد.');
    }

    public function pause(Request $request, PatientMedicationPlan $plan)
    {
        [$user, $access] = $this->owner();
        abort_unless((int) $plan->patient_id === (int) $access->patient_id && (int) $plan->user_id === (int) $user->id, 404);
        $plan->update(['status' => 'paused']);
        return back()->with('success', 'یادآوری شخصی متوقف شد؛ این کار نسخه یا دستور پزشک را تغییر نمی‌دهد.');
    }
}
