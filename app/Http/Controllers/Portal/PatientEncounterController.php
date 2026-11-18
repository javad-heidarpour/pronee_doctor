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
        $this->authorizeAppointment($appointment);
        $doctor = $this->currentDoctor();
        $this->authorizeClinicalView($doctor);

        abort_unless($appointment->patient_id, 404, 'این نوبت هنوز به پرونده بیمار متصل نشده است.');

        $encounters = PatientEncounter::query()
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $appointment->patient_id)
            ->whereIn('clinic_id', $this->accessibleClinicIds($doctor))
            ->with(['author:id,name,family', 'clinic:id,name', 'prescriptions'])
            ->orderByDesc('created_at')
            ->get();

        $audit->handle('patient.encounters.listed', $appointment, ['doctor_id' => $doctor->id]);

        return Inertia::render('Portal/Encounters/Index', [
            'doctor' => $doctor,
            'appointment' => $appointment->load('patient:id,name,phone,national_code', 'clinic:id,name'),
            'encounters' => $encounters,
        ]);
    }

    public function create(Appointment $appointment)
    {
        $this->authorizeAppointment($appointment);
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);

        abort_unless($appointment->patient_id && $appointment->status !== 'cancelled', 404);
        $patient = Patient::query()->whereKey($appointment->patient_id)
            ->where('doctor_id', $doctor->id)->firstOrFail();

        return Inertia::render('Portal/Encounters/Editor', [
            'doctor' => $doctor,
            'appointment' => $appointment->load('clinic:id,name'),
            'patient' => $patient,
            'context' => $this->clinicalContext($patient, $doctor->id),
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

    public function show(PatientEncounter $encounter)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeEncounter($encounter, $doctor);
        $this->authorizeClinicalView($doctor);

        $encounter->load(['patient', 'appointment', 'clinic', 'author:id,name,family', 'prescriptions']);

        return Inertia::render('Portal/Encounters/Editor', [
            'doctor' => $doctor,
            'appointment' => $encounter->appointment->load('clinic:id,name'),
            'patient' => $encounter->patient,
            'context' => $encounter->clinical_snapshot ?? [],
            'encounter' => $encounter,
            'amendsEncounter' => null,
        ]);
    }

    public function amend(PatientEncounter $encounter)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeEncounter($encounter, $doctor);
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        abort_unless($encounter->status === PatientEncounter::STATUS_SIGNED, 409);
        $encounter->load(['patient', 'appointment', 'clinic', 'prescriptions']);

        return Inertia::render('Portal/Encounters/Editor', [
            'doctor' => $doctor,
            'appointment' => $encounter->appointment->load('clinic:id,name'),
            'patient' => $encounter->patient,
            'context' => $encounter->clinical_snapshot ?? [],
            'encounter' => null,
            'amendsEncounter' => $encounter,
        ]);
    }

    public function update(
        StorePatientEncounterRequest $request,
        PatientEncounter $encounter,
        UpdatePatientEncounterAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
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

        return [
            'captured_at' => now()->toIso8601String(),
            'allergies' => array_values(array_filter($records, fn ($record) => $record['kind'] === 'allergy')),
            'medications' => array_values(array_filter($records, fn ($record) => $record['kind'] === 'medication')),
            'vitals' => $vitals,
        ];
    }
}