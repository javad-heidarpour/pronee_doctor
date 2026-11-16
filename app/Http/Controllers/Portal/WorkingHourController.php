<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\WorkingHours\DeleteWorkingHourAction;
use App\Actions\Portal\WorkingHours\ListWorkingHoursAction;
use App\Actions\Portal\WorkingHours\SaveWorkingHourAction;
use App\Actions\Portal\WorkingHours\ToggleWorkingHourAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Doctor;
use App\Models\DoctorWorkingHour;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkingHourController extends Controller
{
    use HasActiveDoctor;

    /**
     * نمایش ساعات کاری پزشک فعال.
     */
    public function index(Request $request, ListWorkingHoursAction $action)
    {
        $doctor = $this->currentDoctor();

        return Inertia::render(
            'Portal/WorkingHours',
            $action->handle($doctor, $request)
        );
    }

    /**
     * ایجاد بازه کاری جدید.
     */
    public function store(Request $request, SaveWorkingHourAction $action)
    {
        $doctor = $this->currentDoctor();

        $validated = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:addresses,id'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        if (!$this->clinicBelongsToDoctor($doctor, (int) $validated['clinic_id'])) {
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
        Request $request,
        DoctorWorkingHour $workingHour,
        SaveWorkingHourAction $action
    ) {
        $doctor = $this->currentDoctor();

        $this->authorizeOwnership($workingHour, (int) $doctor->id);

        $validated = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:addresses,id'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

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

    private function clinicBelongsToDoctor(Doctor $doctor, int $clinicId): bool
    {
        return $doctor->address()->whereKey($clinicId)->exists();
    }

    private function overlapMessage(DoctorWorkingHour $duplicate): string
    {
        return __('working_hours.duplicate', [
            'day' => __('working_hours.days.' . $duplicate->day_of_week),
            'clinic' => $duplicate->clinic?->name
                ?? __('working_hours.unknown_clinic'),
        ]);
    }

    private function authorizeOwnership(
        DoctorWorkingHour $workingHour,
        int $doctorId
    ): void {
        abort_if(
            (int) $workingHour->doctor_id !== $doctorId,
            403,
            __('working_hours.forbidden')
        );
    }
}