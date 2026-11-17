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
use App\Http\Requests\Portal\SavePatientClinicalRecordRequest;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Http\Controllers\Portal\Concerns\AuthorizesPortalAppointments;

use Illuminate\Validation\ValidationException;
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
        BuildPatientIndexOptionsAction $optionsAction
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('appointments.view', $doctor);
        $filters = $request->validated();

        $clinicIds = $this->accessibleClinicIds($doctor);

        $result = $action->handle($doctor, $clinicIds, $filters);
        $options = $optionsAction->handle($doctor, $clinicIds);

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

        $audit->handle('patient.record.viewed', $appointment, [
            'doctor_id' => $doctor->id,
        ]);

        return Inertia::render('Portal/Patients/Record', [
            'doctor' => $doctor,
            'visits' => $visits,
        ]);
    }

    public function storeClinicalRecord(
        SavePatientClinicalRecordRequest $request,
        Patient $patient,
        SavePatientClinicalRecordAction $action
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.manage', $doctor);
        $patient = $this->authorizePatientClinicalAccess($patient, $doctor);

        $record = $action->handle($patient, $request->validated());

        return back()->with('success', 'اطلاعات پزشکی بیمار با موفقیت ثبت شد.');
    }

    public function updateClinicalRecord(
        SavePatientClinicalRecordRequest $request,
        Patient $patient,
        PatientClinicalRecord $record,
        SavePatientClinicalRecordAction $action
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.manage', $doctor);
        $patient = $this->authorizePatientClinicalAccess($patient, $doctor);

        abort_unless(
            (int) $record->patient_id === (int) $patient->id &&
            (int) $record->doctor_id === (int) $doctor->id,
            404
        );

        $action->handle($patient, $request->validated(), $record);

        return back()->with('success', 'اطلاعات پزشکی بیمار با موفقیت ویرایش شد.');
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
        UpdateAppointmentStatusAction $action
    ) {
        $this->authorizeAppointment($appointment);
        $this->authorizeSecretaryPermission('appointments.manage');

        try {
            $result = $action->handle(
                $appointment,
                $request->validated('status')
            );

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
        UpdateAppointmentDescriptionAction $action
    ) {
        $this->authorizeAppointment($appointment);
        // ثبت جزئیات ویزیت علاوه بر مدیریت اطلاعات بیمار، وضعیت نوبت را نیز به visited تغییر می‌دهد.
        $this->authorizeSecretaryPermission('appointments.manage');
        $this->authorizeSecretaryPermission('patients.manage');

        $data = UpdateAppointmentDescriptionData::fromValidated($request->validated());
        $updated = $action->handle($appointment, $data);

        if (!$updated) {
            return back()->with(
                'error',
                'امکان ثبت یا ویرایش اطلاعات نوبت لغوشده وجود ندارد.'
            );
        }

        return back()->with('success', 'اطلاعات نوبت با موفقیت ذخیره شد.');
    }

}