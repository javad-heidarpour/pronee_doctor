<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\WorkingHours\DeleteWorkingHourAction;
use App\Actions\Portal\WorkingHours\ListWorkingHoursAction;
use App\Actions\Portal\WorkingHours\SaveWorkingHourAction;
use App\Actions\Portal\WorkingHours\ToggleWorkingHourAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\SaveWorkingHourRequest;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Doctor;
use App\Models\DoctorWorkingHour;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkingHourController extends Controller
{
    use HasActiveDoctor;
    use AuthorizesPortalSchedules;

    /**
     * نمایش ساعات کاری پزشک فعال.
     */
    public function index(Request $request, ListWorkingHoursAction $action)
    {
        $doctor = $this->currentDoctor();

        return Inertia::render(
            'Portal/WorkingHours',
            $action->handle($doctor, $request, $this->accessibleClinicIds($doctor))
        );
    }

    /**
     * ایجاد بازه کاری جدید.
     */
    public function store(SaveWorkingHourRequest $request, SaveWorkingHourAction $action)
    {
        $doctor = $this->currentDoctor();

        $validated = $request->validated();

        if (!in_array((int) $validated['clinic_id'], $this->accessibleClinicIds($doctor), true)) {
            return back()
                ->withErrors(['clinic_id' => __('working_hours.invalid_clinic')])
                ->withInput();
        }

        $result = $action->handle($doctor, $validated);

        if (!$result['saved']) {
            return back()
                ->withErrors(['duplicate' => $this->overlapMessage($result['duplicate'])])
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
        SaveWorkingHourAction $action
    ) {
        $doctor = $this->currentDoctor();

        $this->authorizeWorkingHour($workingHour, $doctor);

        $validated = $request->validated();

        if (!$this->clinicBelongsToDoctor($doctor, (int) $validated['clinic_id'])) {
            return back()
                ->withErrors(['clinic_id' => __('working_hours.invalid_clinic')])
                ->withInput();
        }

        $result = $action->handle($doctor, $validated, $workingHour);

        if (!$result['saved']) {
            return back()
                ->withErrors(['duplicate' => $this->overlapMessage($result['duplicate'])])
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

        $this->authorizeOwnership($workingHour, (int) $doctor->id);
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

        $this->authorizeOwnership($workingHour, (int) $doctor->id);
        $action->handle($workingHour);

        return back()->with('success', __('working_hours.deleted'));
    }


}