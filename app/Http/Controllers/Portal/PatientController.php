<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Actions\Portal\Appointment\ListPortalAppointmentsAction;
use App\Actions\Portal\Appointment\UpdateAppointmentStatusAction;
use App\Actions\Portal\Appointment\UpdateAppointmentDescriptionAction;
use App\Http\Requests\Portal\ListPortalAppointmentsRequest;
use App\Http\Requests\Portal\UpdateAppointmentStatusRequest;
use App\Http\Requests\Portal\UpdateAppointmentDescriptionRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\SurgeryType;
use App\Models\TimeSlot;
use App\Models\VisitReason;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;




class PatientController extends Controller
{
    use HasActiveDoctor;



    public function index(
        ListPortalAppointmentsRequest $request,
        ListPortalAppointmentsAction $action
    ) {
        $doctor = $this->currentDoctor();
        $filters = $request->validated();

        $result = $action->handle(
            $doctor,
            $this->accessibleClinicIds($doctor),
            $filters
        );

        $clinics = $this->accessibleClinicsQuery($doctor)
            ->select('addresses.id', 'addresses.name')
            ->get();

        $visitReasons = VisitReason::query()
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        $surgeryTypes = SurgeryType::query()
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        return Inertia::render('Portal/Patients/Index', [
            'doctor' => $doctor,
            'appointments' => $result['appointments'],
            'stats' => $result['stats'],
            'clinics' => $clinics,
            'visitReasons' => $visitReasons,
            'surgeryTypes' => $surgeryTypes,
            'filters' => $filters,
        ]);
    }

    /**
     * دریافت تاریخ‌ها و ساعت‌های آزاد مطب‌های قابل دسترس.
     */
    public function availableDates(Request $request, ListAvailableDatesAction $action)
    {
        $validated = $request->validate([
            'clinic_id' => ['required', 'integer'],
        ]);

        $doctor = $this->currentDoctor();
        $clinicId = (int) $validated['clinic_id'];
        $this->authorizeClinicAccess($clinicId, $doctor);

        return response()->json([
            'success' => true,
            'dates' => $action->handle($doctor, $clinicId),
        ]);
    }

    public function availableSlots(
        Request $request,
        ListAvailableSlotsAction $action
    ) {
        $validated = $request->validate([
            'clinic_id' => ['required', 'integer'],
        ]);

        $doctor = $this->currentDoctor();
        $clinicId = (int) $validated['clinic_id'];
        $this->authorizeClinicAccess($clinicId, $doctor);

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

    public function show(Appointment $appointment)
    {
        $this->authorizeAppointment($appointment);


        $appointment->load(['user', 'timeSlot', 'clinic.city', 'visitReason',]);
        $doctor = $this->currentDoctor();
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

    private function authorizeAppointment(Appointment $appointment)
    {
        $user = Auth::guard('web')->user();

        abort_unless($user, 401, 'کاربر وارد نشده است.');

        $doctor = $this->currentDoctor();

        abort_unless($doctor, 403, 'پروفایل پزشک یافت نشد.');

        abort_unless(
            (int) $appointment->doctor_id === (int) $doctor->id &&
            in_array((int) $appointment->clinic_id, $this->accessibleClinicIds($doctor), true),
            403,
            'شما اجازه دسترسی به این نوبت را ندارید.'
        );

        return true;
    }

    public function updateDescription(
        UpdateAppointmentDescriptionRequest $request,
        Appointment $appointment,
        UpdateAppointmentDescriptionAction $action
    ) {
        $this->authorizeAppointment($appointment);

        if (in_array($appointment->status, ['cancelled', 'canceled'], true)) {
            return back()->with(
                'error',
                'امکان ثبت یا ویرایش اطلاعات نوبت لغوشده وجود ندارد.'
            );
        }

        $action->handle($appointment, $request->validated());

        return back()->with('success', 'اطلاعات نوبت با موفقیت ذخیره شد.');
    }

}