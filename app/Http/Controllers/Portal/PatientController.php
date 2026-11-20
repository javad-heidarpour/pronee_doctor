<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Actions\Portal\Appointment\ListPortalAppointmentsAction;
use App\Actions\Portal\Patient\ListPortalPatientsAction;
use App\Actions\Portal\Patient\LoadPortalPatientRecordAction;
use App\Actions\Portal\Appointment\ExportPortalAppointmentsAction;
use App\Actions\Portal\Appointment\LoadPortalAppointmentDetailsAction;
use App\Actions\Portal\Appointment\BuildPatientIndexOptionsAction;
use App\Actions\Portal\Appointment\ListAvailableDatesAction;
use App\Actions\Portal\Appointment\ListAvailableSlotsAction;
use App\Actions\Portal\Appointment\UpdateAppointmentStatusAction;
use App\Actions\Portal\Appointment\UpdateAppointmentDescriptionAction;
use App\DTOs\Portal\Appointment\UpdateAppointmentDescriptionData;
use App\Http\Requests\Portal\ListPortalAppointmentsRequest;
use App\Http\Requests\Portal\AvailableClinicRequest;
use App\Http\Requests\Portal\UpdateAppointmentStatusRequest;
use App\Http\Requests\Portal\UpdateAppointmentDescriptionRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientClinicalRecord;
use App\Actions\Portal\Patient\SavePatientClinicalRecordAction;
use App\Actions\Portal\Patient\ArchivePatientClinicalRecordAction;
use App\Actions\Portal\Patient\RestorePatientClinicalRecordAction;
use App\Actions\Portal\Patient\RecordPatientVitalSignAction;
use App\Http\Requests\Portal\StorePatientVitalSignRequest;
use App\Http\Requests\Portal\SavePatientClinicalRecordRequest;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Http\Controllers\Portal\Concerns\AuthorizesPortalAppointments;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PatientController extends Controller
{
    use HasActiveDoctor;
    use AuthorizesPortalAppointments;

    public function directory(
        \Illuminate\Http\Request $request,
        ListPortalPatientsAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.view', $doctor);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $patients = $action->handle(
            $doctor,
            $this->accessibleClinicIds($doctor),
            $validated['search'] ?? null
        );

        // The directory exposes patient identifiers, so access itself is auditable.
        // Search terms and patient identifiers are intentionally excluded from metadata.
        $audit->handle('patients.directory.viewed', null, [
            'doctor_id' => $doctor->id,
        ]);

        return Inertia::render('Portal/Patients/Directory', [
            'doctor' => $doctor,
            'patients' => $patients,
            'filters' => ['search' => $validated['search'] ?? ''],
        ]);
    }

    public function index(
        ListPortalAppointmentsRequest $request,
        ListPortalAppointmentsAction $action,
        BuildPatientIndexOptionsAction $optionsAction,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('appointments.view', $doctor);
        $filters = $request->validated();

        $clinicIds = $this->accessibleClinicIds($doctor);

        $result = $action->handle($doctor, $clinicIds, $filters);
        $options = $optionsAction->handle($doctor, $clinicIds);

        // Appointment rows expose patient identifiers and visit context.
        // Record access without persisting search terms or patient values.
        $audit->handle('appointments.listed', null, ['doctor_id' => $doctor->id]);

        return Inertia::render('Portal/Patients/Index', [
            'doctor' => $doctor,
            'appointments' => $result['appointments'],
            'stats' => $result['stats'],
            'clinics' => $options['clinics'],
            'visitReasons' => $options['visitReasons'],
            'surgeryTypes' => $options['surgeryTypes'],
            'filters' => $filters,
        ]);
    }

    public function export(
        ListPortalAppointmentsRequest $request,
        ExportPortalAppointmentsAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('appointments.view', $doctor);

        $response = $action->handle(
            $doctor,
            $this->accessibleClinicIds($doctor),
            $request->validated()
        );

        $audit->handle('appointments.exported', null, [
            'doctor_id' => $doctor->id,
        ]);

        return $response;
    }

    /**
     * دریافت تاریخ‌ها و ساعت‌های آزاد مطب‌های قابل دسترس.
     */
    public function availableDates(AvailableClinicRequest $request, ListAvailableDatesAction $action)
    {
        $validated = $request->validated();

        $doctor = $this->currentDoctor();
        $clinicId = (int) $validated['clinic_id'];
        $this->authorizeClinicAccess($clinicId, $doctor);
        $this->authorizeSecretaryPermission('shifts.view', $doctor);

        return response()->json([
            'success' => true,
            'dates' => $action->handle($doctor, $clinicId),
        ]);
    }

    public function availableSlots(
        AvailableClinicRequest $request,
        ListAvailableSlotsAction $action
    ) {
        $validated = $request->validated();

        $doctor = $this->currentDoctor();
        $clinicId = (int) $validated['clinic_id'];
        $this->authorizeClinicAccess($clinicId, $doctor);
        $this->authorizeSecretaryPermission('shifts.view', $doctor);

        $slots = $action->handle($doctor, $clinicId);

        if ($slots === null) {
            return response()->json([
                'message' => 'مطب انتخاب‌شده معتبر نیست.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'slots' => $slots,
        ]);
    }

    public function record(
        Appointment $appointment,
        LoadPortalPatientRecordAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $this->authorizeAppointment($appointment);

        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.view', $doctor);

        $visits = $action->handle(
            $appointment,
            $this->accessibleClinicIds($doctor)
        );

        $canManageClinicalRecords = $this->hasSecretaryPermission($doctor, 'patients.clinical.manage');
        $canViewClinicalRecords = $canManageClinicalRecords ||
            $this->hasSecretaryPermission($doctor, 'patients.clinical.view');

        if ($canViewClinicalRecords) {
            $visits->loadMissing(['patient.clinicalRecords.relatedRecord', 'patient.clinicalRecords.relatedAllergyRecord', 'patient.archivedClinicalRecords', 'patient.vitalSigns', 'patient.problems']);
            $audit->handle('patient.clinical_records.viewed', $appointment, [
                'doctor_id' => $doctor->id,
            ]);
            $audit->handle('patient.vital_signs.viewed', $appointment, [
                'doctor_id' => $doctor->id,
            ]);
        } else {
            $visits->each(function (Appointment $visit): void {
                $visit->patient?->setRelation('clinicalRecords', collect());
                $visit->patient?->setRelation('vitalSigns', collect());
            });
        }

        $audit->handle('patient.record.viewed', $appointment, [
            'doctor_id' => $doctor->id,
        ]);

        return Inertia::render('Portal/Patients/Record', [
            'doctor' => $doctor,
            'visits' => $visits,
            'canViewClinicalRecords' => $canViewClinicalRecords,
            'canManageClinicalRecords' => $canManageClinicalRecords,
        ]);
    }

    public function storeVitalSign(
        StorePatientVitalSignRequest $request,
        Patient $patient,
        RecordPatientVitalSignAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        abort_unless($this->hasSecretaryPermission($doctor, 'patients.clinical.manage'), 403);
        $patient = $this->authorizePatientClinicalAccess($patient, $doctor);

        $vitalSign = $action->handle($patient, $request->validated());

        $audit->handle('patient.vital_sign.recorded', $vitalSign, [
            'doctor_id' => $doctor->id,
            'changed_fields' => ['measurement_type'],
        ]);

        return back()->with('success', 'اندازه‌گیری علائم حیاتی ثبت شد.');
    }

    public function storeClinicalRecord(
        SavePatientClinicalRecordRequest $request,
        Patient $patient,
        SavePatientClinicalRecordAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        abort_unless($this->hasSecretaryPermission($doctor, 'patients.clinical.manage'), 403);
        $patient = $this->authorizePatientClinicalAccess($patient, $doctor);

        $record = $action->handle($patient, $request->validated());
        return back()->with('success', 'اطلاعات پزشکی بیمار با موفقیت ثبت شد.');
    }

    public function updateClinicalRecord(
        SavePatientClinicalRecordRequest $request,
        Patient $patient,
        PatientClinicalRecord $record,
        SavePatientClinicalRecordAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        abort_unless($this->hasSecretaryPermission($doctor, 'patients.clinical.manage'), 403);
        $patient = $this->authorizePatientClinicalAccess($patient, $doctor);

        abort_unless(
            (int) $record->patient_id === (int) $patient->id &&
            (int) $record->doctor_id === (int) $doctor->id,
            404
        );

        $updatedRecord = $action->handle($patient, $request->validated(), $record);
        return back()->with('success', 'اطلاعات پزشکی بیمار با موفقیت ویرایش شد.');
    }

    public function archiveClinicalRecord(
        Patient $patient,
        PatientClinicalRecord $record,
        ArchivePatientClinicalRecordAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        abort_unless($this->hasSecretaryPermission($doctor, 'patients.clinical.manage'), 403);
        $patient = $this->authorizePatientClinicalAccess($patient, $doctor);

        abort_unless(
            (int) $record->patient_id === (int) $patient->id &&
            (int) $record->doctor_id === (int) $doctor->id,
            404
        );

        $action->handle($patient, $record);

        return back()->with('success', 'سابقه پزشکی به‌صورت امن بایگانی شد.');
    }

    public function restoreClinicalRecord(
        Patient $patient,
        int $record,
        RestorePatientClinicalRecordAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        abort_unless($this->hasSecretaryPermission($doctor, 'patients.clinical.manage'), 403);
        $patient = $this->authorizePatientClinicalAccess($patient, $doctor);

        $archivedRecord = PatientClinicalRecord::withTrashed()
            ->whereKey($record)
            ->where('patient_id', $patient->id)
            ->where('doctor_id', $doctor->id)
            ->whereNotNull('deleted_at')
            ->firstOrFail();

        $restoredRecord = $action->handle($patient, $archivedRecord);

        $audit->handle('patient.clinical_record.restored', $restoredRecord, [
            'doctor_id' => $doctor->id,
            'restored' => true,
        ]);

        return back()->with('success', 'سابقه پزشکی با موفقیت بازیابی شد.');
    }

    private function hasSecretaryPermission(\App\Models\Doctor $doctor, string $permission): bool
    {
        $user = Auth::guard('web')->user();

        if (!$user || $user->role !== 'secretary') {
            return true;
        }

        $assignment = $user->doctors()
            ->where('doctors.id', $doctor->id)
            ->first();

        if (!$assignment) {
            return false;
        }

        $permissions = $assignment->pivot?->permissions ?? [];
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        return is_array($permissions) && in_array($permission, $permissions, true);
    }

    private function authorizePatientClinicalAccess(Patient $patient, \App\Models\Doctor $doctor): Patient
    {
        $scopedPatient = Patient::query()
            ->whereKey($patient->id)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        $hasAccessibleVisit = $scopedPatient->appointments()
            ->whereIn('clinic_id', $this->accessibleClinicIds($doctor))
            ->exists();

        abort_unless($hasAccessibleVisit, 403, 'شما به پرونده این بیمار دسترسی ندارید.');

        return $scopedPatient;
    }

    public function show(
        Appointment $appointment,
        LoadPortalAppointmentDetailsAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $this->authorizeAppointment($appointment);

        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.view', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        $appointment = $action->handle($appointment, $clinicIds);

        $audit->handle('appointment.viewed', $appointment, [
            'doctor_id' => $doctor->id,
        ]);

        return Inertia::render('Portal/Patients/Show', [
            'doctor' => $doctor,
            'appointment' => $appointment,
        ]);
    }

    public function updateStatus(
        UpdateAppointmentStatusRequest $request,
        Appointment $appointment,
        UpdateAppointmentStatusAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeAppointment($appointment);
        $this->authorizeSecretaryPermission('appointments.manage', $doctor);

        try {
            $result = DB::transaction(function () use ($action, $appointment, $request, $audit, $doctor): string {
                $result = $action->handle($appointment, $request->validated('status'));

                if ($result === 'updated') {
                    $audit->handle('appointment.status.updated', $appointment, [
                        'doctor_id' => $doctor->id,
                        'status_changed' => true,
                    ]);
                }

                return $result;
            });

            if ($result === 'already_visited') {
                return back()->with(
                    'error',
                    'وضعیت این نوبت قبلاً به ویزیت‌شده تغییر کرده و امکان تغییر مجدد آن وجود ندارد.'
                );
            }

            if ($result === 'visit_completion_not_allowed') {
                return back()->with(
                    'error',
                    'ثبت وضعیت ویزیت‌شده از این بخش امکان‌پذیر نیست.'
                );
            }

            return back()->with('success', 'وضعیت نوبت با موفقیت تغییر کرد.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'در تغییر وضعیت نوبت مشکلی رخ داد. لطفاً مجدداً تلاش کنید.'
            );
        }
    }

    public function updateDescription(
        UpdateAppointmentDescriptionRequest $request,
        Appointment $appointment,
        UpdateAppointmentDescriptionAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeAppointment($appointment);
        // ثبت جزئیات ویزیت علاوه بر مدیریت اطلاعات بیمار، وضعیت نوبت را نیز به visited تغییر می‌دهد.
        $this->authorizeSecretaryPermission('appointments.manage', $doctor);
        $this->authorizeSecretaryPermission('patients.manage', $doctor);

        $validated = $request->validated();
        $data = UpdateAppointmentDescriptionData::fromValidated($validated);

        $updated = DB::transaction(function () use ($action, $appointment, $data, $audit, $doctor, $validated): bool {
            $updated = $action->handle($appointment, $data);

            if ($updated) {
                // Never place visit notes, diagnoses, or other PHI in audit metadata.
                $audit->handle('appointment.visit_details.updated', $appointment, [
                    'doctor_id' => $doctor->id,
                    'status_changed' => true,
                    'changed_fields' => array_keys($validated),
                ]);
            }

            return $updated;
        });

        if (!$updated) {
            return back()->with(
                'error',
                'امکان ثبت یا ویرایش اطلاعات نوبت لغوشده وجود ندارد.'
            );
        }

        return back()->with('success', 'اطلاعات نوبت با موفقیت ذخیره شد.');
    }

}