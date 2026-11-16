<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Shifts\CreateTimeSlotAction;
use App\Actions\Portal\Shifts\DeleteTimeSlotAction;
use App\Actions\Portal\Shifts\GenerateTimeSlotsAction;
use App\Actions\Portal\Shifts\ListTimeSlotsAction;
use App\Actions\Portal\Shifts\ToggleTimeSlotAction;
use App\Actions\Portal\Shifts\UpdateTimeSlotAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Doctor;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShiftController extends Controller
{
    use HasActiveDoctor;

    private function authorizeClinic(Doctor $doctor, int $clinicId): void
    {
        abort_unless(
            in_array($clinicId, $this->accessibleClinicIds($doctor), true),
            403,
            'شما به این مطب دسترسی ندارید.'
        );
    }

    private function authorizeSlot(TimeSlot $slot, int $doctorId): void
    {
        abort_unless(
            (int) $slot->doctor_id === $doctorId &&
            in_array((int) $slot->clinic_id, $this->accessibleClinicIds(), true),
            403,
            'دسترسی غیرمجاز'
        );
    }

    public function index(Request $request, ListTimeSlotsAction $action)
    {
        $doctor = $this->currentDoctor();

        return Inertia::render(
            'Portal/Shifts/Index',
            $action->handle($doctor, $request, $this->accessibleClinicIds($doctor))
        );
    }

    public function store(Request $request, CreateTimeSlotAction $action)
    {
        $doctor = $this->currentDoctor();

        $validated = $request->validate([
            'clinic_id' => 'required|exists:addresses,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ], [
            'clinic_id.required' => 'انتخاب مطب الزامی است',
            'date.required' => 'انتخاب تاریخ الزامی است',
            'date.after_or_equal' => 'تاریخ نمی‌تواند در گذشته باشد',
            'start_time.required' => 'ساعت شروع الزامی است',
            'end_time.required' => 'ساعت پایان الزامی است',
            'end_time.after' => 'ساعت پایان باید بعد از ساعت شروع باشد',
        ]);

        $this->authorizeClinic($doctor, (int) $validated['clinic_id']);

        if (!$action->handle($doctor, $validated)) {
            return back()->withErrors([
                'duplicate' => 'این بازه زمانی با یک نوبت دیگر تداخل دارد یا قبلاً ایجاد شده است.',
            ])->withInput();
        }

        return back()->with('success', 'نوبت با موفقیت ایجاد شد.');
    }

    public function update(
        Request $request,
        TimeSlot $slot,
        UpdateTimeSlotAction $action
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSlot($slot, (int) $doctor->id);

        if ($slot->isBooked()) {
            return back()->withErrors([
                'error' => 'نوبت رزرو شده قابل ویرایش نیست.',
            ]);
        }

        $validated = $request->validate([
            'clinic_id' => 'required|exists:addresses,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

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
        $this->authorizeSlot($slot, (int) $doctor->id);

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
        $this->authorizeSlot($slot, (int) $doctor->id);

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
        Request $request,
        GenerateTimeSlotsAction $action
    ) {
        $doctor = $this->currentDoctor();

        $validated = $request->validate([
            'weeks_count' => 'required|integer|min:1|max:52',
            'clinic_id' => auth('web')->user()?->role === 'secretary'
                ? 'required|exists:addresses,id'
                : 'nullable|exists:addresses,id',
        ], [
            'weeks_count.required' => 'تعداد هفته‌ها الزامی است',
            'weeks_count.min' => 'حداقل ۱ هفته',
            'weeks_count.max' => 'حداکثر ۵۲ هفته (یک سال)',
        ]);

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
