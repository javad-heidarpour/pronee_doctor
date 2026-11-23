<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Patient\CreatePatientEncounterAction;
use App\Actions\Portal\Patient\SignPatientEncounterAction;
use App\Actions\Portal\Patient\UpdatePatientEncounterAction;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\AuthorizesPortalAppointments;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Http\Requests\Portal\StorePatientEncounterRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientClinicalRecord;
use App\Models\PatientEncounter;
use App\Models\PatientVitalSign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

final class PatientEncounterController extends Controller
{
    use HasActiveDoctor;
    use AuthorizesPortalAppointments;

    public function index(Appointment $appointment, RecordSecurityAuditEventAction $audit)
    {
        $this->authorizeClinicalRole();
        $this->authorizeAppointment($appointment);
        $doctor = $this->currentDoctor();
        $this->authorizeClinicalView($doctor);

        abort_unless($appointment->patient_id, 404, 'این نوبت هنوز به پرونده بیمار متصل نشده است.');

        $encounters = PatientEncounter::query()
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $appointment->patient_id)
            ->whereIn('clinic_id', $this->accessibleClinicIds($doctor))
            ->with(['author:id,name,family', 'clinic:id,name', 'amendedFrom:id'])
            ->withCount('prescriptions')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PatientEncounter $item): array => $this->encounterListPayload($item))
            ->values();

        $appointment->load('patient:id,name', 'clinic:id,name');
        $audit->handle('patient.encounters.listed', $appointment, ['doctor_id' => $doctor->id]);

        return Inertia::render('Portal/Encounters/Index', [
            'doctor' => $doctor,
            'appointment' => $this->appointmentPayload($appointment) + [
                'patient' => $appointment->patient ? $this->patientPayload($appointment->patient) : null,
            ],
            'encounters' => $encounters,
        ]);
    }

    public function create(Appointment $appointment, RecordSecurityAuditEventAction $audit)
    {
        $this->authorizeClinicalRole();
        $this->authorizeAppointment($appointment);
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);

        abort_unless($appointment->patient_id && $appointment->status !== 'cancelled', 404);
        $patient = Patient::query()->whereKey($appointment->patient_id)
            ->where('doctor_id', $doctor->id)->firstOrFail();
        $context = $this->clinicalContext($patient, $doctor->id);

        $audit->handle('patient.encounter.create_form_viewed', $appointment, [
            'doctor_id' => $doctor->id,
        ]);

        return Inertia::render('Portal/Encounters/Editor', [
            'doctor' => $doctor,
            'appointment' => $this->appointmentPayload($appointment->load('clinic:id,name')),
            'patient' => $this->patientPayload($patient),
            'context' => $context,
            'encounter' => null,
            'amendsEncounter' => null,
        ]);
    }

    public function store(
        StorePatientEncounterRequest $request,
        Appointment $appointment,
        CreatePatientEncounterAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $this->authorizeClinicalRole();
        $this->authorizeAppointment($appointment);

        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        abort_unless($appointment->patient_id && $appointment->status !== 'cancelled', 422);

        $patient = Patient::query()->whereKey($appointment->patient_id)
            ->where('doctor_id', $doctor->id)->firstOrFail();
        $data = $request->validated();
        if (!empty($data['amends_encounter_id'])) {
            $original = PatientEncounter::query()
                ->whereKey($data['amends_encounter_id'])
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->where('appointment_id', $appointment->id)
                ->where('clinic_id', $appointment->clinic_id)
                ->where('status', PatientEncounter::STATUS_SIGNED)
                ->firstOrFail();
            $data['amends_encounter_id'] = $original->id;
        } else {
            $data['amends_encounter_id'] = null;
        }
        $data['clinical_snapshot'] = $this->clinicalContext($patient, $doctor->id);

        $encounter = $action->handle($doctor, Auth::guard('web')->user(), $appointment, $data);

        $audit->handle('patient.encounter.created', $encounter, [
            'doctor_id' => $doctor->id,
            'changed_fields' => ['clinical_note', 'prescriptions'],
        ]);

        return redirect()->route('portal.encounters.show', $encounter)
            ->with('success', 'پیش‌نویس یادداشت ویزیت ذخیره شد.');
    }

    public function show(PatientEncounter $encounter, RecordSecurityAuditEventAction $audit)
    {
        $this->authorizeClinicalRole();
        $doctor = $this->currentDoctor();
        $this->authorizeEncounter($encounter, $doctor);
        $this->authorizeClinicalView($doctor);

        // Resolve related records through the same doctor and clinic scope; do not
        // serialize the full appointment or patient models into the clinical editor.
        $encounter->load(['signer:id,name,family', 'prescriptions']);
        $patient = Patient::query()
            ->whereKey($encounter->patient_id)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();
        $appointment = Appointment::query()
            ->whereKey($encounter->appointment_id)
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->where('clinic_id', $encounter->clinic_id)
            ->with('clinic:id,name')
            ->firstOrFail();

        $audit->handle('patient.encounter.viewed', $encounter, ['doctor_id' => $doctor->id]);

        return Inertia::render('Portal/Encounters/Editor', [
            'doctor' => $doctor,
            'appointment' => $this->appointmentPayload($appointment),
            'patient' => $this->patientPayload($patient),
            'context' => $encounter->clinical_snapshot ?? [],
            'encounter' => $this->encounterPayload($encounter, true),
            'amendsEncounter' => null,
        ]);
    }

    public function amend(PatientEncounter $encounter, RecordSecurityAuditEventAction $audit)
    {
        $this->authorizeClinicalRole();
        $doctor = $this->currentDoctor();
        $this->authorizeEncounter($encounter, $doctor);
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        abort_unless($encounter->status === PatientEncounter::STATUS_SIGNED, 409);
        $encounter->load('prescriptions');
        $patient = Patient::query()
            ->whereKey($encounter->patient_id)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();
        $appointment = Appointment::query()
            ->whereKey($encounter->appointment_id)
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->where('clinic_id', $encounter->clinic_id)
            ->with('clinic:id,name')
            ->firstOrFail();

        $audit->handle('patient.encounter.amend_form_viewed', $encounter, ['doctor_id' => $doctor->id]);

        return Inertia::render('Portal/Encounters/Editor', [
            'doctor' => $doctor,
            'appointment' => $this->appointmentPayload($appointment),
            'patient' => $this->patientPayload($patient),
            'context' => $this->clinicalContext($patient, $doctor->id),
            'encounter' => null,
            'amendsEncounter' => $this->encounterPayload($encounter),
        ]);
    }

    public function update(
        StorePatientEncounterRequest $request,
        PatientEncounter $encounter,
        UpdatePatientEncounterAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $this->authorizeClinicalRole();
        $doctor = $this->currentDoctor();

        $this->authorizeEncounter($encounter, $doctor);
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        $patient = $encounter->patient()->firstOrFail();
        $data = $request->validated();
        $data['clinical_snapshot'] = $this->clinicalContext($patient, $doctor->id);

        $updated = $action->handle($encounter, $data);
        $audit->handle('patient.encounter.updated', $updated, [
            'doctor_id' => $doctor->id,
            'changed_fields' => ['clinical_note', 'prescriptions'],
        ]);

        return back()->with('success', 'پیش‌نویس ویزیت به‌روزرسانی شد.');
    }

    public function sign(
        PatientEncounter $encounter,
        SignPatientEncounterAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $this->authorizeClinicalRole();
        $doctor = $this->currentDoctor();
        $this->authorizeEncounter($encounter, $doctor);
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        $this->authorizeDoctorOnly();

        $signed = $action->handle($encounter, Auth::guard('web')->user());
        $audit->handle('patient.encounter.signed', $signed, [
            'doctor_id' => $doctor->id,
            'status_changed' => true,
        ]);

        return back()->with('success', 'یادداشت ویزیت نهایی و قفل شد.');
    }



    private function encounterListPayload(PatientEncounter $encounter): array
    {
        return [
            'id' => $encounter->id,
            'diagnosis' => $encounter->diagnosis,
            'note_type' => $encounter->note_type,
            'status' => $encounter->status,
            'created_at' => $encounter->created_at?->toIso8601String(),
            'author' => $encounter->author ? [
                'name' => $encounter->author->name,
                'family' => $encounter->author->family,
            ] : null,
            'amended_from' => $encounter->amendedFrom ? [
                'id' => $encounter->amendedFrom->id,
            ] : null,
            'assessment' => $encounter->assessment,
            'clinic' => $encounter->clinic ? [
                'id' => $encounter->clinic->id,
                'name' => $encounter->clinic->name,
            ] : null,
            'prescriptions_count' => (int) $encounter->prescriptions_count,
        ];
    }

    private function encounterPayload(PatientEncounter $encounter, bool $includeSigner = false): array
    {
        $payload = [
            'id' => $encounter->id,
            'status' => $encounter->status,
            'note_type' => $encounter->note_type,
            'subjective' => $encounter->subjective,
            'objective' => $encounter->objective,
            'assessment' => $encounter->assessment,
            'plan' => $encounter->plan,
            'diagnosis' => $encounter->diagnosis,
            'diagnosis_code' => $encounter->diagnosis_code,
            'reviewed_allergies' => (bool) $encounter->reviewed_allergies,
            'reviewed_medications' => (bool) $encounter->reviewed_medications,
            'reviewed_vitals' => (bool) $encounter->reviewed_vitals,
            'signed_at' => $encounter->signed_at?->toIso8601String(),
            'prescriptions' => $encounter->prescriptions->map(fn ($prescription): array => [
                'id' => $prescription->id,
                'medication_name' => $prescription->medication_name,
                'strength' => $prescription->strength,
                'dosage' => $prescription->dosage,
                'route' => $prescription->route,
                'frequency' => $prescription->frequency,
                'duration_days' => $prescription->duration_days,
                'quantity' => $prescription->quantity,
                'instructions' => $prescription->instructions,
            ])->values()->all(),
        ];

        if ($includeSigner) {
            $payload['signer'] = $encounter->signer ? [
                'id' => $encounter->signer->id,
                'name' => $encounter->signer->name,
                'family' => $encounter->signer->family,
            ] : null;
        }

        return $payload;
    }

    private function appointmentPayload(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'clinic' => $appointment->clinic ? [
                'id' => $appointment->clinic->id,
                'name' => $appointment->clinic->name,
            ] : null,
        ];
    }

    private function patientPayload(Patient $patient): array
    {
        return [
            'id' => $patient->id,
            'name' => $patient->name,
        ];
    }

    private function authorizeClinicalRole(): void
    {
        abort_unless(
            in_array(Auth::guard('web')->user()?->role, ['doctor', 'secretary'], true),
            403,
            'دسترسی به پرونده بالینی فقط برای پزشک و منشی مجاز تعریف‌شده امکان‌پذیر است.'
        );
    }

    private function authorizeEncounter(PatientEncounter $encounter, $doctor): void
    {
        abort_unless(
            (int) $encounter->doctor_id === (int) $doctor->id &&
            in_array((int) $encounter->clinic_id, $this->accessibleClinicIds($doctor), true),
            404
        );
    }

    private function authorizeClinicalView($doctor): void
    {
        $user = Auth::guard('web')->user();
        if (!$user || $user->role !== 'secretary') {
            return;
        }

        $assignment = $user->doctors()->where('doctors.id', $doctor->id)->first();
        $permissions = $assignment?->pivot?->permissions ?? [];
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        abort_unless(
            is_array($permissions) &&
            (in_array('patients.clinical.view', $permissions, true) ||
             in_array('patients.clinical.manage', $permissions, true)),
            403,
            'مجوز مشاهده یادداشت‌های بالینی برای شما فعال نیست.'
        );
    }

    private function clinicalContext(Patient $patient, int $doctorId): array
    {
        $records = PatientClinicalRecord::query()
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patient->id)
            ->where('is_active', true)
            ->whereIn('kind', ['allergy', 'medication'])
            ->orderBy('kind')
            ->get(['id', 'kind', 'title', 'description', 'dosage', 'frequency', 'start_date', 'end_date'])
            ->map(fn (PatientClinicalRecord $record) => $record->toArray())
            ->values()
            ->all();

        $vitals = PatientVitalSign::query()
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patient->id)
            ->orderByDesc('measured_at')
            ->limit(20)
            ->get(['id', 'measurement_type', 'value', 'unit', 'measured_at', 'note'])
            ->map(fn (PatientVitalSign $vital) => $vital->toArray())
            ->values()
            ->all();

        $snapshot = [
            'captured_at' => now()->toIso8601String(),
            'allergies' => array_values(array_filter($records, fn ($record) => $record['kind'] === 'allergy')),
            'medications' => array_values(array_filter($records, fn ($record) => $record['kind'] === 'medication')),
            'vitals' => $vitals,
        ];
        $snapshot['decision_support'] = app(\App\Services\ClinicalDecisionSupport::class)->evaluate($snapshot);
        return $snapshot;
    }
}