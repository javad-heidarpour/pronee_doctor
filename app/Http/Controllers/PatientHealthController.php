<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientAccountAccess;
use App\Models\PatientAccessRequest;
use App\Models\PatientEncounter;
use App\Models\PatientFollowUpTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class PatientHealthController extends Controller
{
    public function index()
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && $user->role === 'user', 403);

        $accesses = PatientAccountAccess::query()->active()
            ->where('user_id', $user->id)
            ->with(['patient.doctor:id,name,family,specialty_title'])
            ->get();

        $pending = PatientAccessRequest::query()
            ->where('user_id', $user->id)->where('status', 'pending')->exists();

        if ($accesses->isEmpty()) {
            return Inertia::render('Patient/Health', [
                'hasAccess' => false,
                'pendingRequest' => $pending,
                'accesses' => [],
            ]);
        }

        $selected = $accesses->first();
        $accessType = $selected->access_type;
        $patient = Patient::query()->select(['id', 'doctor_id', 'name'])
            ->with([
                'doctor:id,name,family,specialty_title,photo',
                'appointments' => fn ($q) => $q->select([
                    'id', 'patient_id', 'doctor_id', 'clinic_id', 'appointment_date',
                    'appointment_time', 'status', 'visit_number',
                ])->with('clinic:id,name')->orderByDesc('appointment_date')->limit(25),
            ])->findOrFail($selected->patient_id);

        if ($accessType === 'owner') {
            $patient->load([
                'clinicalRecords' => fn ($q) => $q->select([
                    'id', 'patient_id', 'doctor_id', 'kind', 'title', 'description',
                    'dosage', 'frequency', 'start_date', 'end_date', 'is_active',
                ])->where('is_active', true)->limit(40),
                'vitalSigns' => fn ($q) => $q->select([
                    'id', 'patient_id', 'doctor_id', 'measurement_type', 'value',
                    'unit', 'measured_at', 'note',
                ])->orderByDesc('measured_at')->limit(40),
            ]);
        } else {
            $patient->setRelation('clinicalRecords', collect());
            $patient->setRelation('vitalSigns', collect());
        }

        $encounters = $accessType === 'owner'
            ? PatientEncounter::query()
                ->where('patient_id', $patient->id)
                ->where('status', PatientEncounter::STATUS_SIGNED)
                ->select(['id', 'doctor_id', 'patient_id', 'diagnosis', 'subjective', 'plan', 'signed_at'])
                ->with('doctor:id,name,family,specialty_title')
                ->latest('signed_at')->limit(20)->get()
            : collect();

        $followUps = PatientFollowUpTask::query()
            ->where('patient_id', $patient->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('due_at')->limit(30)->get([
                'id', 'title', 'description', 'due_at', 'priority', 'status',
            ]);

        $caregivers = $accessType === 'owner'
            ? PatientAccountAccess::query()->active()
                ->where('patient_id', $patient->id)
                ->where('access_type', 'caregiver')
                ->with('user:id,name,family,email')
                ->get()
            : collect();

        return Inertia::render('Patient/Health', [
            'hasAccess' => true,
            'pendingRequest' => $pending,
            'accesses' => $accesses->map(fn ($access) => [
                'id' => $access->id,
                'access_type' => $access->access_type,
                'patient' => ['id' => $access->patient->id, 'name' => $access->patient->name],
            ]),
            'accessType' => $accessType,
            'patient' => $patient,
            'encounters' => $encounters,
            'followUps' => $followUps,
            'caregivers' => $caregivers,
        ]);
    }

    public function requestAccess(Request $request)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && $user->role === 'user', 403);

        $data = $request->validate([
            'national_code' => ['required', 'string', 'regex:/^[0-9۰-۹]{10}$/'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
        ]);

        $nationalCode = strtr($data['national_code'], ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']);
        $normalizePhone = static function (?string $value): string {
            $digits = preg_replace('/\\D+/', '', strtr((string) $value, [
                '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            ]));
            if (str_starts_with($digits, '0098')) $digits = '0' . substr($digits, 4);
            elseif (str_starts_with($digits, '98') && strlen($digits) === 12) $digits = '0' . substr($digits, 2);
            return $digits;
        };

        $submittedPhone = $normalizePhone($data['phone']);
        $patient = Patient::query()->where('national_code', $nationalCode)->get()
            ->first(fn (Patient $candidate) => $normalizePhone($candidate->phone) === $submittedPhone);

        // Do not reveal whether a record exists; clinic staff must verify identity.
        if ($patient) {
            $accessRequest = PatientAccessRequest::query()->firstOrNew([
                'user_id' => $user->id,
                'patient_id' => $patient->id,
            ]);
            if (!$accessRequest->exists || $accessRequest->status !== 'approved') {
                $accessRequest->fill([
                    'doctor_id' => $patient->doctor_id,
                    'status' => 'pending',
                    'reviewed_by_user_id' => null,
                    'reviewed_at' => null,
                    'review_note' => null,
                ])->save();
            }
        }

        return back()->with('success', 'اگر اطلاعات با پرونده‌ای تطبیق داشته باشد، درخواست برای بررسی هویت به مرکز درمانی ارسال می‌شود.');
    }

    public function grantCaregiver(Request $request)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && $user->role === 'user', 403);
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);

        $ownerAccess = PatientAccountAccess::query()->active()
            ->where('user_id', $user->id)->where('access_type', 'owner')->firstOrFail();

        $caregiver = User::query()->where('email', $data['email'])->where('role', 'user')->first();
        abort_unless($caregiver && (int) $caregiver->id !== (int) $user->id, 422, 'حساب مراقب یافت نشد یا معتبر نیست.');

        PatientAccountAccess::query()->updateOrCreate(
            ['patient_id' => $ownerAccess->patient_id, 'user_id' => $caregiver->id],
            [
                'granted_by_user_id' => $user->id,
                'access_type' => 'caregiver',
                'permissions' => ['view_summary', 'view_appointments', 'view_followups'],
                'expires_at' => now()->addDays(90),
                'revoked_at' => null,
            ]
        );

        return back()->with('success', 'دسترسی مراقب برای ۹۰ روز فعال شد. هر زمان می‌توانید آن را لغو کنید.');
    }

    public function revokeCaregiver(PatientAccountAccess $access)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && $user->role === 'user', 403);

        $owner = PatientAccountAccess::query()->active()
            ->where('user_id', $user->id)->where('access_type', 'owner')
            ->where('patient_id', $access->patient_id)->firstOrFail();

        abort_unless($access->access_type === 'caregiver', 404);
        $access->update(['revoked_at' => now()]);

        return back()->with('success', 'دسترسی مراقب لغو شد.');
    }
}
