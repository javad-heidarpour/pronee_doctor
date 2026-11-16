<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\WorkingHours\BuildWorkingHourConflictMessageAction;
use App\Actions\Portal\WorkingHours\DeleteWorkingHourAction;
use App\Actions\Portal\WorkingHours\ListWorkingHoursAction;
use App\Actions\Portal\WorkingHours\SaveWorkingHourAction;
use App\Actions\Portal\WorkingHours\ToggleWorkingHourAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\SaveWorkingHourRequest;
use App\Http\Requests\Portal\ListWorkingHoursRequest;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Http\Controllers\Portal\Concerns\AuthorizesPortalSchedules;
use App\Models\DoctorWorkingHour;
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

        return Inertia::render(
            'Portal/WorkingHours',
            $action->handle($doctor, $request->validated(), $this->accessibleClinicIds($doctor))
        );
    }

    /**
     * ایجاد بازه کاری جدید.
     */
    public function store(
        SaveWorkingHourRequest $request,
        SaveWorkingHourAction $action,
        BuildWorkingHourConflictMessageAction $messageAction
    )
    {
        $doctor = $this->currentDoctor();

        $validated = $request->validated();

        $this->authorizeClinic($doctor, (int) $validated['clinic_id']);

        $result = $action->handle($doctor, $validated);

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

        $this->authorizeWorkingHour($workingHour, $doctor);

        $validated = $request->validated();

        $this->authorizeClinic($doctor, (int) $validated['clinic_id']);

        $result = $action->handle($doctor, $validated, $workingHour);

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

        $this->authorizeWorkingHour($workingHour, $doctor);
        $action->handle($workingHour);

        return back()->with('success', __('working_hours.status_changed'));
    }

    /**
     * حذف بازه کاری.
     */
    public function destroy(
        DoctorWorkingHour $workingHour,
        DeleteWorkingHourAction $action
    ) {
        $doctor = $this->currentDoctor();

        $this->authorizeWorkingHour($workingHour, $doctor);
        $action->handle($workingHour);

        return back()->with('success', __('working_hours.deleted'));
    }

}
