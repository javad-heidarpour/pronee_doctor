<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientAccountAccess;
use App\Models\PatientAccessRequest;
use App\Models\PatientEncounter;
use App\Models\PatientFollowUpTask;
use App\Models\PatientHealthObservation;
use App\Models\PatientMedicationPlan;
use App\Models\PatientCarePathway;
use App\Models\PatientCarePathwayStep;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\PatientEncounterPrescription;
use App\Models\User;
use App\Services\PatientHealthIntelligence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class PatientHealthController extends Controller
{
    public function index(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $user = $this->authenticatedPatient();
        $selection = $request->validate([
            'access_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $accesses = PatientAccountAccess::query()->active()
            ->where('user_id', $user->id)
            ->with(['patient.doctor:id,name,family,specialty_title'])
            ->get();

        $visibleAccesses = $accesses
            ->filter(fn (PatientAccountAccess $access): bool => $access->allows('view_summary'))
            ->values();

        $pending = PatientAccessRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($visibleAccesses->isEmpty()) {
            return Inertia::render('Patient/Health', [
                'hasAccess' => false,
                'pendingRequest' => $pending,
                'accesses' => [],
            ]);
        }

        $selected = isset($selection['access_id'])
            ? $visibleAccesses->firstWhere('id', (int) $selection['access_id'])
            : $visibleAccesses->first();

        // Only active grants that explicitly allow the summary may be selected.
        abort_unless($selected, 404);

        $accessType = $selected->access_type;
        $canViewRecords = $selected->allows('view_records');
        $canViewAppointments = $selected->allows('view_appointments');
        $canViewFollowUps = $selected->allows('view_followups');
        $canManageCaregivers = $selected->allows('manage_caregivers');

        $patient = Patient::query()
            ->select(['id', 'doctor_id', 'name'])
            ->with(['doctor:id,name,family,specialty_title,photo'])
            ->findOrFail($selected->patient_id);

        if ($canViewAppointments) {
            $patient->load([
                'appointments' => fn ($q) => $q->select([
                    'id', 'patient_id', 'doctor_id', 'clinic_id', 'appointment_date',
                    'appointment_time', 'status', 'visit_number',
                ])->with('clinic:id,name')->orderByDesc('appointment_date')->limit(25),
            ]);
        } else {
            $patient->setRelation('appointments', collect());
        }

        if ($canViewRecords) {
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

        $encounters = $canViewRecords
            ? PatientEncounter::query()
                ->where('patient_id', $patient->id)
                ->where('status', PatientEncounter::STATUS_SIGNED)
                ->select(['id', 'doctor_id', 'patient_id', 'diagnosis', 'subjective', 'plan', 'signed_at'])
                ->with('doctor:id,name,family,specialty_title')
                ->latest('signed_at')->limit(20)->get()
            : collect();

        $followUps = $canViewFollowUps
            ? PatientFollowUpTask::query()
                ->where('patient_id', $patient->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->orderBy('due_at')->limit(30)->get([
                    'id', 'title', 'description', 'due_at', 'priority', 'status',
                ])
            : collect();

        // Medication reminders and patient-entered observations remain private to the owner.
        $medicationPlans = $accessType === 'owner'
            ? PatientMedicationPlan::query()->where('patient_id', $patient->id)->where('user_id', $user->id)
                ->where('status', 'active')
                ->with(['prescription:id,encounter_id,medication_name,strength,dosage,route,frequency,duration_days,instructions',
                    'doses' => fn ($q) => $q->whereDate('scheduled_for', now()->toDateString())])
                ->orderByDesc('created_at')->get()
            : collect();

        $availablePrescriptions = $canViewRecords
            ? PatientEncounterPrescription::query()
                ->whereHas('encounter', fn ($q) => $q->where('patient_id', $patient->id)->where('status', PatientEncounter::STATUS_SIGNED))
                ->with('encounter:id,signed_at')->latest('created_at')->limit(50)
                ->get(['id','encounter_id','medication_name','strength','dosage','route','frequency','duration_days','instructions'])
            : collect();

        $observations = $accessType === 'owner'
            ? PatientHealthObservation::query()->where('patient_id', $patient->id)->where('user_id', $user->id)->latest('observed_at')->limit(60)->get()
            : collect();

        $carePathways = $canViewRecords
            ? PatientCarePathway::query()->where('patient_id', $patient->id)->where('status', 'active')
                ->with(['steps' => fn ($q) => $q->orderBy('sort_order')])
                ->orderByDesc('activated_at')->limit(10)->get()
            : collect();

        $caregivers = $canManageCaregivers
            ? PatientAccountAccess::query()->active()
                ->where('patient_id', $patient->id)
                ->where('access_type', 'caregiver')
                ->with('user:id,name,family,email')
                ->get()
            : collect();

        $intelligence = $accessType === 'owner'
            ? app(PatientHealthIntelligence::class)->build($patient, $followUps, $observations)
            : ['summary' => [], 'insights' => [], 'trends' => []];

        $audit->handle('patient.portal.health.viewed', $selected);

        return Inertia::render('Patient/Health', [
            'hasAccess' => true,
            'pendingRequest' => $pending,
            'accesses' => $visibleAccesses->map(fn ($access) => [
                'id' => $access->id,
                'access_type' => $access->access_type,
                'patient' => ['id' => $access->patient->id, 'name' => $access->patient->name],
                'doctor' => [
                    'name' => $access->patient->doctor?->name,
                    'family' => $access->patient->doctor?->family,
                    'specialty_title' => $access->patient->doctor?->specialty_title,
                ],
            ])->values(),
            'selectedAccessId' => $selected->id,
            'accessType' => $accessType,
            'patient' => $patient,
            'encounters' => $encounters,
            'followUps' => $followUps,
            'caregivers' => $caregivers,
            'intelligence' => $intelligence,
            'observations' => $observations,
            'medicationPlans' => $medicationPlans,
            'availablePrescriptions' => $availablePrescriptions,
            'carePathways' => $carePathways,
        ]);
    }

    public function reportCarePathwayStep(Request $request, int $pathway, int $step, RecordSecurityAuditEventAction $audit)
    {
        $user = $this->authenticatedPatient();

        DB::transaction(function () use ($user, $pathway, $step, $audit): void {
            // The pathway lock is shared with staff verification, preventing
            // concurrent patient/staff transitions from overwriting each other.
            $activePathway = PatientCarePathway::query()
                ->whereKey($pathway)
                ->where('status', 'active')
                ->lockForUpdate()
                ->firstOrFail();

            PatientAccountAccess::query()->active()
                ->where('user_id', $user->id)
                ->where('access_type', 'owner')
                ->where('patient_id', $activePathway->patient_id)
                ->lockForUpdate()
                ->firstOrFail();

            $item = PatientCarePathwayStep::query()
                ->whereKey($step)
                ->where('pathway_id', $activePathway->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $item->status === 'pending',
                422,
                'این گام در وضعیت قابل گزارش نیست.'
            );

            $item->update([
                'status' => 'patient_reported',
                'patient_reported_at' => now(),
            ]);

            // Do not persist the patient transition unless its audit event also succeeds.
            $audit->handle('patient.care_pathway.step_reported', $item, [
                'pathway_id' => $activePathway->id,
            ]);
        }, 3);

        return back()->with('success', 'انجام این گام برای بررسی به مرکز درمانی گزارش شد.');
    }

    public function storeObservation(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $user = $this->authenticatedPatient();

        $validated = $request->validate([
            'access_id' => ['required', 'integer', 'min:1'],
            'measurement_type' => ['required', 'string', Rule::in(array_keys(PatientHealthObservation::MEASUREMENTS))],
            'value' => ['required', 'numeric'],
            'observed_at' => ['required', 'date', 'before_or_equal:now', 'after_or_equal:'.now()->subYears(2)->toDateTimeString()],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $ownerAccess = $this->ownedOwnerAccess($user, accessId: (int) $validated['access_id']);

        $definition = PatientHealthObservation::MEASUREMENTS[$validated['measurement_type']];
        abort_if(
            (float) $validated['value'] < $definition['min'] ||
            (float) $validated['value'] > $definition['max'],
            422,
            'مقدار واردشده خارج از محدوده قابل ثبت است؛ واحد و مقدار را بررسی کنید.'
        );

        $observation = PatientHealthObservation::query()->create([
            'patient_id' => $ownerAccess->patient_id,
            'user_id' => $user->id,
            'measurement_type' => $validated['measurement_type'],
            'value' => $validated['value'],
            'unit' => $definition['unit'],
            'observed_at' => $validated['observed_at'],
            'note' => $validated['note'] ?? null,
        ]);

        $audit->handle('patient.health_observation.created', $observation);

        return back()->with('success', 'اندازه‌گیری شخصی شما ثبت شد و با برچسب «ثبت‌شده توسط بیمار» در تحلیل‌ها نمایش داده می‌شود.');
    }

    public function destroyObservation(int $observation, RecordSecurityAuditEventAction $audit)
    {
        $user = $this->authenticatedPatient();

        $record = PatientHealthObservation::query()
            ->whereKey($observation)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Resolve ownership from the observation itself so a second linked
        // patient record is not accidentally checked against the first grant.
        $this->ownedOwnerAccess($user, patientId: (int) $record->patient_id);

        $record->delete();
        $audit->handle('patient.health_observation.deleted', $record);

        return back()->with('success', 'اندازه‌گیری شخصی حذف شد.');
    }

    public function requestAccess(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $user = $this->authenticatedPatient();

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
        $matches = Patient::query()->where('national_code', $nationalCode)->get()
            ->filter(fn (Patient $candidate) => $normalizePhone($candidate->phone) === $submittedPhone)
            ->values();

        // A duplicate identity match is ambiguous. Fail closed instead of linking
        // the account to whichever doctor's patient row happens to be returned first.
        $patient = $matches->count() === 1 ? $matches->first() : null;

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

        // Log the attempt without recording national code, phone, or match outcome.
        $audit->handle('patient.access_request.submitted');

        return back()->with('success', 'اگر اطلاعات با پرونده‌ای تطبیق داشته باشد، درخواست برای بررسی هویت به مرکز درمانی ارسال می‌شود.');
    }

    public function grantCaregiver(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $user = $this->authenticatedPatient();
        $data = $request->validate([
            'access_id' => ['required', 'integer', 'min:1'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $ownerAccess = $this->ownedOwnerAccess($user, accessId: (int) $data['access_id']);

        $caregiver = User::query()->where('email', $data['email'])->where('role', 'user')->where('is_active', true)->first();
        abort_unless($caregiver && (int) $caregiver->id !== (int) $user->id, 422, 'حساب مراقب یافت نشد یا معتبر نیست.');

        $caregiverAccess = DB::transaction(function () use ($ownerAccess, $caregiver, $user): PatientAccountAccess {
            $existingAccess = PatientAccountAccess::query()
                ->where('patient_id', $ownerAccess->patient_id)
                ->where('user_id', $caregiver->id)
                ->lockForUpdate()
                ->first();

            abort_if(
                $existingAccess?->access_type === 'owner',
                409,
                'این حساب از قبل مالک پرونده است و نمی‌توان دسترسی آن را به مراقب تنزل داد.'
            );

            return PatientAccountAccess::query()->updateOrCreate(
                ['patient_id' => $ownerAccess->patient_id, 'user_id' => $caregiver->id],
                [
                    'granted_by_user_id' => $user->id,
                    'access_type' => 'caregiver',
                    'permissions' => ['view_summary', 'view_appointments', 'view_followups'],
                    'expires_at' => now()->addDays(90),
                    'revoked_at' => null,
                ]
            );
        });

        $audit->handle('patient.caregiver.access_granted', $caregiverAccess);

        return back()->with('success', 'دسترسی مراقب برای ۹۰ روز فعال شد. هر زمان می‌توانید آن را لغو کنید.');
    }

    public function revokeCaregiver(PatientAccountAccess $access, RecordSecurityAuditEventAction $audit)
    {
        $user = $this->authenticatedPatient();

        $owner = PatientAccountAccess::query()->active()
            ->where('user_id', $user->id)->where('access_type', 'owner')
            ->where('patient_id', $access->patient_id)->firstOrFail();

        abort_unless($access->access_type === 'caregiver', 404);
        $access->update(['revoked_at' => now()]);
        $audit->handle('patient.caregiver.access_revoked', $access);

        return back()->with('success', 'دسترسی مراقب لغو شد.');
    }
    private function ownedOwnerAccess(
        User $user,
        ?int $accessId = null,
        ?int $patientId = null
    ): PatientAccountAccess {
        $query = PatientAccountAccess::query()->active()
            ->where('user_id', $user->id)
            ->where('access_type', 'owner');

        if ($accessId !== null) {
            $query->whereKey($accessId);
        }

        if ($patientId !== null) {
            $query->where('patient_id', $patientId);
        }

        return $query->firstOrFail();
    }

    private function authenticatedPatient(): User
    {
        $user = Auth::guard('web')->user();

        abort_unless(
            $user instanceof User && $user->role === 'user' && $user->is_active,
            403
        );

        return $user;
    }

}
