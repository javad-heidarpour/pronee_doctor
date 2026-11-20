<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\WorkingHours\BuildWorkingHourConflictMessageAction;
use App\Actions\Portal\WorkingHours\DeleteWorkingHourAction;
use App\Actions\Portal\WorkingHours\ListWorkingHoursAction;
use App\Actions\Portal\WorkingHours\SaveWorkingHourAction;
use App\Actions\Portal\WorkingHours\ToggleWorkingHourAction;
use App\Actions\Portal\WorkingHours\CreateDoctorHolidayAction;
use App\Actions\Portal\WorkingHours\DeleteDoctorHolidayAction;
use App\DTOs\Portal\Scheduling\WorkingHourData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\SaveWorkingHourRequest;
use App\Http\Requests\Portal\ListWorkingHoursRequest;
use App\Http\Requests\Portal\StoreDoctorHolidayRequest;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Http\Controllers\Portal\Concerns\AuthorizesPortalSchedules;
use App\Models\DoctorWorkingHour;
use App\Models\DoctorHoliday;
use Inertia\Inertia;

class WorkingHourController extends Controller
{
    use HasActiveDoctor;
    use AuthorizesPortalSchedules;

    /**
     * نمایش ساعات کاری پزشک فعال.
     */
    public function index(ListWorkingHoursRequest $request, ListWorkingHoursAction $action)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('working-hours.view', $doctor);

        $filters = $request->validated();

        if (!empty($filters['clinic_id'])) {
            $this->authorizeClinic($doctor, (int) $filters['clinic_id']);
        }

        return Inertia::render(
            'Portal/WorkingHours',
            $action->handle($doctor, $filters, $this->accessibleClinicIds($doctor))
        );
    }

    /**
     * ایجاد بازه کاری جدید.
     */
    public function store(
        SaveWorkingHourRequest $request,
        SaveWorkingHourAction $action,
        BuildWorkingHourConflictMessageAction $messageAction
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('working-hours.manage', $doctor);

        $data = WorkingHourData::fromValidated($request->validated());

        $this->authorizeClinic($doctor, $data->clinicId);

        $result = $action->handle($doctor, $data);

        if (!$result['saved']) {
            return back()
                ->withErrors(['duplicate' => $messageAction->handle($result['duplicate'])])
                ->withInput();
        }

        return back()->with('success', __('working_hours.created'));
    }

    /**
     * ویرایش بازه کاری.
     */
    public function update(
        SaveWorkingHourRequest $request,
        DoctorWorkingHour $workingHour,
        SaveWorkingHourAction $action,
        BuildWorkingHourConflictMessageAction $messageAction
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('working-hours.manage', $doctor);

        $this->authorizeWorkingHour($workingHour, $doctor);

        $data = WorkingHourData::fromValidated($request->validated());

        $this->authorizeClinic($doctor, $data->clinicId);

        $result = $action->handle($doctor, $data, $workingHour);

        if (!$result['saved']) {
            return back()
                ->withErrors(['duplicate' => $messageAction->handle($result['duplicate'])])
                ->withInput();
        }

        return back()->with('success', __('working_hours.updated'));
    }

    /**
     * فعال یا غیرفعال کردن بازه کاری.
     */
    public function toggle(
        DoctorWorkingHour $workingHour,
        ToggleWorkingHourAction $action
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('working-hours.manage', $doctor);

        $this->authorizeWorkingHour($workingHour, $doctor);

        if (!$action->handle($workingHour)) {
            return back()->withErrors([
                'error' => 'این بازه با یک شیفت فعال دیگر هم‌پوشانی دارد و قابل فعال‌سازی نیست.',
            ]);
        }

        return back()->with('success', __('working_hours.status_changed'));
    }

    public function storeHoliday(
        StoreDoctorHolidayRequest $request,
        CreateDoctorHolidayAction $action
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('working-hours.manage', $doctor);

        $data = $request->validated();
        $this->authorizeClinic($doctor, (int) $data['clinic_id']);

        $action->handle($doctor, $data, auth('web')->id());

        return back()->with(
            'success',
            'تعطیلی ثبت شد. ساعت‌های آزاد آن روز بسته شدند؛ پس از حذف تعطیلی، برای بازسازی ساعت‌ها تولید نوبت را اجرا کنید.'
        );
    }

    public function destroyHoliday(
        DoctorHoliday $holiday,
        DeleteDoctorHolidayAction $action
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('working-hours.manage', $doctor);

        abort_unless((int) $holiday->doctor_id === (int) $doctor->id, 403);
        $this->authorizeClinic($doctor, (int) $holiday->clinic_id);
        $action->handle($doctor, $holiday);

        return back()->with(
            'success',
            'تعطیلی حذف شد. برای ایجاد ساعت‌های آزاد آن تاریخ، تولید نوبت را اجرا کنید.'
        );
    }

    /**
     * حذف بازه کاری.
     */
    public function destroy(
        DoctorWorkingHour $workingHour,
        DeleteWorkingHourAction $action
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('working-hours.manage', $doctor);

        $this->authorizeWorkingHour($workingHour, $doctor);
        $action->handle($workingHour);

        return back()->with('success', __('working_hours.deleted'));
    }
}
