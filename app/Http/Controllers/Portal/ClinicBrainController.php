<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use App\Models\ClinicBrainWaitlistEntry;
use App\Models\Patient;
use App\Models\TimeSlot;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use App\Services\ClinicBrain\ClinicBrainAnalyzer;
use App\Services\ClinicBrain\WaitlistOfferEligibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class ClinicBrainController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request, ClinicBrainAnalyzer $analyzer, WaitlistOfferEligibility $eligibility)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'secretary'], true), 403);

        $doctor = $this->currentDoctor();
        $clinicIds = $this->accessibleClinicIds($doctor);

        if ($user->role === 'secretary') {
            $assignment = $user->doctors()->where('doctors.id', $doctor->id)->first();
            $permissions = $assignment?->pivot?->permissions ?? [];
            if (is_string($permissions)) {
                $permissions = json_decode($permissions, true) ?: [];
            }
            abort_unless(is_array($permissions) && (
                in_array('appointments.view', $permissions, true)
                || in_array('appointments.manage', $permissions, true)
                || in_array('shifts.view', $permissions, true)
            ), 403, 'مجوز مشاهده تحلیل عملیاتی برای شما فعال نیست.');
        }

        $selectedClinicId = $request->integer('clinic_id') ?: null;
        if ($selectedClinicId !== null) {
            abort_unless(in_array($selectedClinicId, $clinicIds, true), 403);
        }

        $brain = $analyzer->analyze($doctor->id, $clinicIds, $selectedClinicId);
        $waitlistQuery = ClinicBrainWaitlistEntry::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('status', ['waiting', 'offered'])
            ->with(['patient:id,name,phone', 'clinic:id,name', 'offeredSlot:id,clinic_id,date,start_time,end_time'])
            ->latest();

        if ($clinicIds === []) {
            $waitlistQuery->whereRaw('1 = 0');
        } else {
            $waitlistQuery->where(function ($query) use ($clinicIds): void {
                $query->whereIn('clinic_id', $clinicIds)->orWhereNull('clinic_id');
            });
        }

        if ($selectedClinicId !== null) {
            $waitlistQuery->where(function ($query) use ($selectedClinicId): void {
                $query->whereNull('clinic_id')->orWhere('clinic_id', $selectedClinicId);
            });
        }

        $waitlist = $waitlistQuery->limit(50)->get()->map(function (ClinicBrainWaitlistEntry $entry) use ($brain, $eligibility) {
            $matches = collect($brain['available_slots'])->filter(fn (array $slot): bool => $eligibility->matches(
                $entry->status,
                (bool) $entry->contact_consent,
                $entry->clinic_id === null ? null : (int) $entry->clinic_id,
                (int) $slot['clinic_id'],
                $entry->preferred_from->toDateString(),
                $entry->preferred_until->toDateString(),
                $slot['date'],
                $entry->preferred_period,
                $slot['start_time']
            ))->take(3)->values();

            $entry->setAttribute('matching_slots', $matches);
            return $entry;
        });

        return Inertia::render('Portal/ClinicBrain/Index', [
            'doctor' => [
                'id' => $doctor->id, 'name' => $doctor->name,
                'family' => $doctor->family, 'caption' => $doctor->caption,
            ],
            'clinics' => Address::query()->whereIn('id', $clinicIds)->orderBy('name')->get(['id', 'name']),
            'patients' => Patient::query()->where('doctor_id', $doctor->id)->orderBy('name')->get(['id', 'name', 'phone']),
            'waitlist' => $waitlist,
            'selectedClinicId' => $selectedClinicId,
            'brain' => $brain,
        ]);
    }

    public function storeWaitlist(Request $request)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'secretary'], true), 403);
        $doctor = $this->currentDoctor();
        $clinicIds = $this->accessibleClinicIds($doctor);

        if ($user->role === 'secretary') {
            $this->authorizeSecretaryPermission('appointments.manage', $doctor);
        }

        $data = $request->validate([
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')->where('doctor_id', $doctor->id)],
            'clinic_id' => ['nullable', 'integer', Rule::in($clinicIds)],
            'preferred_from' => ['required', 'date', 'after_or_equal:today'],
            'preferred_until' => ['required', 'date', 'after_or_equal:preferred_from'],
            'preferred_period' => ['required', Rule::in(['any', 'morning', 'afternoon'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'contact_consent' => ['accepted'],
        ]);

        ClinicBrainWaitlistEntry::query()->create([
            ...$data,
            'doctor_id' => $doctor->id,
            'clinic_id' => $data['clinic_id'] ?? null,
            'created_by' => $user->id,
            'status' => 'waiting',
        ]);

        return back()->with('success', 'بیمار با رضایت ثبت‌شده به فهرست انتظار اضافه شد.');
    }


    public function offerWaitlistSlot(Request $request, ClinicBrainWaitlistEntry $entry, WaitlistOfferEligibility $eligibility)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'secretary'], true), 403);
        $doctor = $this->currentDoctor();
        abort_unless((int) $entry->doctor_id === (int) $doctor->id, 404);
        abort_unless(in_array((int) $entry->clinic_id, $this->accessibleClinicIds($doctor), true) || $entry->clinic_id === null, 403);
        if ($user->role === 'secretary') $this->authorizeSecretaryPermission('appointments.manage', $doctor);

        $data = $request->validate(['slot_id' => ['required', 'integer']]);
        abort_unless($entry->status === 'waiting' && $entry->contact_consent, 422, 'درخواست باید فعال و دارای رضایت تماس باشد.');

        DB::transaction(function () use ($data, $entry, $doctor, $eligibility) {
            $lockedEntry = ClinicBrainWaitlistEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedEntry->status === 'waiting' && $lockedEntry->contact_consent, 422, 'درخواست باید فعال و دارای رضایت تماس باشد.');

            $slot = TimeSlot::query()->whereKey($data['slot_id'])
                ->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $this->accessibleClinicIds($doctor))
                ->whereDate('date', '>=', today()->toDateString())
                ->whereDate('date', '<=', today()->addDays(13)->toDateString())
                ->lockForUpdate()->firstOrFail();

            $date = $slot->date->toDateString();
            abort_unless($slot->status === 'available' && $eligibility->matches(
                $lockedEntry->status,
                (bool) $lockedEntry->contact_consent,
                $lockedEntry->clinic_id === null ? null : (int) $lockedEntry->clinic_id,
                (int) $slot->clinic_id,
                $lockedEntry->preferred_from->toDateString(),
                $lockedEntry->preferred_until->toDateString(),
                $date,
                $lockedEntry->preferred_period,
                (string) $slot->start_time
            ), 422, 'این زمان آزاد نیست یا با ترجیحات بیمار مطابقت ندارد.');

            abort_if($slot->appointment()->whereIn('status', ['pending', 'confirmed'])->exists(), 422, 'این زمان قبلاً رزرو شده است.');
            abort_if(ClinicBrainWaitlistEntry::query()->where('offered_slot_id', $slot->id)->where('status', 'offered')->exists(), 422, 'این زمان قبلاً به بیمار دیگری پیشنهاد شده است.');

            $lockedEntry->update(['status' => 'offered', 'offered_slot_id' => $slot->id, 'offered_at' => now()]);
        });

        return back()->with('success', 'پیشنهاد نوبت برای پیگیری تماس ثبت شد؛ هنوز نوبتی رزرو نشده است.');
    }

    public function fulfillWaitlist(Request $request, ClinicBrainWaitlistEntry $entry)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'secretary'], true), 403);
        $doctor = $this->currentDoctor();
        abort_unless((int) $entry->doctor_id === (int) $doctor->id, 404);
        abort_unless(in_array((int) $entry->clinic_id, $this->accessibleClinicIds($doctor), true) || $entry->clinic_id === null, 403);
        if ($user->role === 'secretary') $this->authorizeSecretaryPermission('appointments.manage', $doctor);

        $data = $request->validate(['appointment_id' => ['required', 'integer']]);
        DB::transaction(function () use ($data, $entry, $doctor) {
            $lockedEntry = ClinicBrainWaitlistEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedEntry->status === 'offered' && $lockedEntry->offered_slot_id, 422, 'ابتدا باید یک زمان به بیمار پیشنهاد شده باشد.');
            $offeredSlot = TimeSlot::query()->whereKey($lockedEntry->offered_slot_id)
                ->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $this->accessibleClinicIds($doctor))
                ->lockForUpdate()
                ->firstOrFail();
            $appointment = Appointment::query()->whereKey($data['appointment_id'])
                ->where('doctor_id', $doctor->id)->where('patient_id', $lockedEntry->patient_id)
                ->where('clinic_id', $offeredSlot->clinic_id)
                ->where('time_slot_id', $offeredSlot->id)
                ->whereIn('status', ['pending', 'confirmed'])->firstOrFail();

            $lockedEntry->update(['status' => 'fulfilled', 'fulfilled_appointment_id' => $appointment->id]);
        });

        return back()->with('success', 'تبدیل پیشنهاد به نوبت ثبت‌شده تأیید شد.');
    }

    public function closeWaitlist(ClinicBrainWaitlistEntry $entry)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'secretary'], true), 403);
        $doctor = $this->currentDoctor();

        abort_unless((int) $entry->doctor_id === (int) $doctor->id, 404);
        abort_unless(in_array((int) $entry->clinic_id, $this->accessibleClinicIds($doctor), true) || $entry->clinic_id === null, 403);

        if ($user->role === 'secretary') {
            $this->authorizeSecretaryPermission('appointments.manage', $doctor);
        }

        DB::transaction(function () use ($entry, $doctor): void {
            $lockedEntry = ClinicBrainWaitlistEntry::query()
                ->whereKey($entry->id)
                ->where('doctor_id', $doctor->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                in_array((int) $lockedEntry->clinic_id, $this->accessibleClinicIds($doctor), true)
                    || $lockedEntry->clinic_id === null,
                403
            );
            abort_unless(in_array($lockedEntry->status, ['waiting', 'offered'], true), 422);

            $lockedEntry->update([
                'status' => 'closed',
                'offered_slot_id' => null,
                'offered_at' => null,
            ]);
        });

        return back()->with('success', 'درخواست از فهرست انتظار خارج شد.');
    }
}
