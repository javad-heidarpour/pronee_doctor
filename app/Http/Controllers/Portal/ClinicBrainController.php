<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use App\Models\ClinicBrainWaitlistEntry;
use App\Models\Patient;
use App\Services\ClinicBrain\ClinicBrainAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class ClinicBrainController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request, ClinicBrainAnalyzer $analyzer)
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
            ->whereIn('clinic_id', $clinicIds)
            ->whereIn('status', ['waiting', 'offered'])
            ->with(['patient:id,name,phone', 'clinic:id,name'])
            ->latest();

        if ($selectedClinicId !== null) {
            $waitlistQuery->where(function ($query) use ($selectedClinicId): void {
                $query->whereNull('clinic_id')->orWhere('clinic_id', $selectedClinicId);
            });
        }

        $waitlist = $waitlistQuery->limit(50)->get()->map(function (ClinicBrainWaitlistEntry $entry) use ($brain) {
            $matches = collect($brain['available_slots'])->filter(function (array $slot) use ($entry): bool {
                if ($entry->clinic_id && (int) $entry->clinic_id !== (int) $slot['clinic_id']) {
                    return false;
                }
                if ($slot['date'] < $entry->preferred_from->toDateString() || $slot['date'] > $entry->preferred_until->toDateString()) {
                    return false;
                }
                $hour = (int) substr($slot['start_time'], 0, 2);
                return match ($entry->preferred_period) {
                    'morning' => $hour < 12,
                    'afternoon' => $hour >= 12,
                    default => true,
                };
            })->take(3)->values();

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

        abort_unless(in_array($entry->status, ['waiting', 'offered'], true), 422);
        $entry->update(['status' => 'closed']);

        return back()->with('success', 'درخواست از فهرست انتظار خارج شد.');
    }
}
