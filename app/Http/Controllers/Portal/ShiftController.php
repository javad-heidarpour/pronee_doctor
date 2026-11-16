<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Shifts\CreateTimeSlotAction;
use App\Actions\Portal\Shifts\DeleteTimeSlotAction;
use App\Actions\Portal\Shifts\GenerateTimeSlotsAction;
use App\Actions\Portal\Shifts\ListTimeSlotsAction;
use App\Actions\Portal\Shifts\ToggleTimeSlotAction;
use App\Actions\Portal\Shifts\UpdateTimeSlotAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreTimeSlotRequest;
use App\Http\Requests\Portal\UpdateTimeSlotRequest;
use App\Http\Requests\Portal\GenerateTimeSlotsRequest;
use App\Http\Requests\Portal\ListTimeSlotsRequest;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Http\Controllers\Portal\Concerns\AuthorizesPortalSchedules;
use App\Models\Doctor;
use App\Models\TimeSlot;
use Inertia\Inertia;

class ShiftController extends Controller
{
    use HasActiveDoctor;
    use AuthorizesPortalSchedules;


    public function index(ListTimeSlotsRequest $request, ListTimeSlotsAction $action)
    {
        $doctor = $this->currentDoctor();

        return Inertia::render(
            'Portal/Shifts/Index',
            $action->handle($doctor, $request->validated(), $this->accessibleClinicIds($doctor))
        );
    }

    public function store(StoreTimeSlotRequest $request, CreateTimeSlotAction $action)
    {
        $doctor = $this->currentDoctor();

        $validated = $request->validated();

        $this->authorizeClinic($doctor, (int) $validated['clinic_id']);

        if (!$action->handle($doctor, $validated)) {
            return back()->withErrors([
                'duplicate' => 'این بازه زمانی با یک نوبت دیگر تداخل دارد یا قبلاً ایجاد شده است.',
            ])->withInput();
        }

        return back()->with('success', 'نوبت با موفقیت ایجاد شد.');
    }

    public function update(
        UpdateTimeSlotRequest $request,
        TimeSlot $slot,
        UpdateTimeSlotAction $action
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeTimeSlot($slot, $doctor);

        if ($slot->isBooked()) {
            return back()->withErrors([
                'error' => 'نوبت رزرو شده قابل ویرایش نیست.',
            ]);
        }

        $validated = $request->validated();

        $this->authorizeClinic($doctor, (int) $validated['clinic_id']);

        if (!$action->handle($doctor, $slot, $validated)) {
            return back()->withErrors([
                'duplicate' => 'این بازه زمانی با نوبت دیگری تداخل دارد یا قابل ویرایش نیست.',
            ]);
        }

        return back()->with('success', 'نوبت بروزرسانی شد.');
    }

    public function toggle(TimeSlot $slot, ToggleTimeSlotAction $action)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeTimeSlot($slot, $doctor);

        if ($slot->isBooked()) {
            return back()->withErrors([
                'error' => 'نوبت رزرو شده قابل لغو نیست.',
            ]);
        }

        $newStatus = $action->handle($slot);

        if ($newStatus === null) {
            return back()->withErrors([
                'error' => 'وضعیت نوبت تغییر نکرد؛ ممکن است نوبت رزرو شده یا با بازه دیگری تداخل داشته باشد.',
            ]);
        }

        $message = $newStatus === 'available'
            ? 'نوبت فعال شد.'
            : 'نوبت لغو شد.';

        return back()->with('success', $message);
    }

    public function destroy(TimeSlot $slot, DeleteTimeSlotAction $action)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeTimeSlot($slot, $doctor);

        if ($slot->isBooked()) {
            return back()->withErrors([
                'error' => 'نوبت رزرو شده قابل حذف نیست.',
            ]);
        }

        if (!$action->handle($slot)) {
            return back()->withErrors([
                'error' => 'نوبت رزرو شده قابل حذف نیست.',
            ]);
        }

        return back()->with('success', 'نوبت حذف شد.');
    }

    public function generateSlots(
        GenerateTimeSlotsRequest $request,
        GenerateTimeSlotsAction $action
    ) {
        $doctor = $this->currentDoctor();

        $validated = $request->validated();

        $clinicId = !empty($validated['clinic_id'])
            ? (int) $validated['clinic_id']
            : null;

        if ($clinicId !== null) {
            $this->authorizeClinic($doctor, $clinicId);
        }

        $result = $action->handle(
            $doctor,
            (int) $validated['weeks_count'],
            $clinicId
        );

        if (!$result['has_working_hours']) {
            return back()->withErrors([
                'error' => 'هیچ الگوی شیفت فعالی برای تولید نوبت وجود ندارد. ابتدا شیفت‌ها را تعریف کنید.',
            ]);
        }

        $message = "با موفقیت {$result['generated']} نوبت جدید برای {$validated['weeks_count']} هفته آینده ایجاد شد.";

        if ($result['skipped'] > 0) {
            $message .= " ({$result['skipped']} نوبت تکراری یا دارای تداخل رد شد)";
        }

        return back()->with('success', $message);
    }
}
