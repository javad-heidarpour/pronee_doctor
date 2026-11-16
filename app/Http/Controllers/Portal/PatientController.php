<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Actions\Portal\Appointment\ListPortalAppointmentsAction;
use App\Actions\Portal\Appointment\LoadPortalAppointmentDetailsAction;
use App\Actions\Portal\Appointment\BuildPatientIndexOptionsAction;
use App\Actions\Portal\Appointment\ListAvailableDatesAction;
use App\Actions\Portal\Appointment\ListAvailableSlotsAction;
use App\Actions\Portal\Appointment\UpdateAppointmentStatusAction;
use App\Actions\Portal\Appointment\UpdateAppointmentDescriptionAction;
use App\Http\Requests\Portal\ListPortalAppointmentsRequest;
use App\Http\Requests\Portal\AvailableClinicRequest;
use App\Http\Requests\Portal\UpdateAppointmentStatusRequest;
use App\Http\Requests\Portal\UpdateAppointmentDescriptionRequest;
use App\Models\Appointment;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Http\Controllers\Portal\Concerns\AuthorizesPortalAppointments;

use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PatientController extends Controller
{
    use HasActiveDoctor;
    use AuthorizesPortalAppointments;

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

    public function show(
        Appointment $appointment,
        LoadPortalAppointmentDetailsAction $action
    ) {
        $this->authorizeAppointment($appointment);

        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.view', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        $appointment = $action->handle($appointment, $clinicIds);

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
        $this->authorizeSecretaryPermission('patients.manage');

        $updated = $action->handle($appointment, $request->validated());

        if (!$updated) {
            return back()->with(
                'error',
                'امکان ثبت یا ویرایش اطلاعات نوبت لغوشده وجود ندارد.'
            );
        }

        return back()->with('success', 'اطلاعات نوبت با موفقیت ذخیره شد.');
    }

}