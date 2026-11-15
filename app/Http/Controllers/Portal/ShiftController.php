<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DoctorWorkingHour;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;

class ShiftController extends Controller
{
    use HasActiveDoctor;


    /**
     * بررسی تعلق مطب به پزشک
     */
    private function authorizeClinic($doctor, int $clinicId): void
    {
        $exists = $doctor->address()
            ->whereKey($clinicId)
            ->exists();

        abort_unless($exists, 403, 'شما به این مطب دسترسی ندارید.');
    }

    /**
     * بررسی مالکیت نوبت
     */
    private function authorizeSlot(TimeSlot $slot, int $doctorId): void
    {
        abort_unless(
            (int) $slot->doctor_id === (int) $doctorId,
            403,
            'دسترسی غیرمجاز'
        );
    }

    /**
     * نمایش لیست نوبت‌ها
     */
    public function index(Request $request)
    {
        $doctor = $this->currentDoctor();

        $clinics = $doctor->address()
            ->with('city')
            ->get();

        $query = TimeSlot::where('doctor_id', $doctor->id)
            ->with('clinic.city');

        if ($request->filled('clinic_id')) {
            $query->where('clinic_id', $request->clinic_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('date', 'LIKE', "%{$search}%")
                    ->orWhere('start_time', 'LIKE', "%{$search}%");
            });
        }

        $allowedSortColumns = [
            'date',
            'start_time',
            'end_time',
            'status',
            'created_at',
        ];

        $sortBy = $request->input('sort_by', 'date');

        if (!in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'date';
        }

        $sortDir = strtolower($request->input('sort_dir', 'asc'));

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $slots = $query
            ->orderBy($sortBy, $sortDir)
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => TimeSlot::where('doctor_id', $doctor->id)->count(),

            'available' => TimeSlot::where('doctor_id', $doctor->id)
                ->where('status', 'available')
                ->count(),

            'booked' => TimeSlot::where('doctor_id', $doctor->id)
                ->where('status', 'booked')
                ->count(),

            'cancelled' => TimeSlot::where('doctor_id', $doctor->id)
                ->where('status', 'cancelled')
                ->count(),
        ];

        return Inertia::render('Portal/Shifts/Index', [
            'doctor' => $doctor,
            'clinics' => $clinics,
            'slots' => $slots,
            'stats' => $stats,
            'filters' => $request->only([
                'clinic_id',
                'status',
                'date_from',
                'date_to',
                'search',
                'sort_by',
                'sort_dir',
            ]),
        ]);
    }

    /**
     * ایجاد نوبت تکی
     */
    public function store(Request $request)
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

        $exists = TimeSlot::where('doctor_id', $doctor->id)
            ->where('clinic_id', $validated['clinic_id'])
            ->where('date', $validated['date'])
            ->where('start_time', $validated['start_time'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'duplicate' => 'این نوبت قبلاً برای این تاریخ و ساعت ایجاد شده است.',
            ])->withInput();
        }

        TimeSlot::create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $validated['clinic_id'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => 'available',
        ]);

        return back()->with('success', 'نوبت با موفقیت ایجاد شد.');
    }

    /**
     * ویرایش نوبت
     */
    public function update(Request $request, TimeSlot $slot)
    {
        $doctor = $this->currentDoctor();

        $this->authorizeSlot($slot, $doctor->id);

        if ($slot->status === 'booked') {
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

        $duplicate = TimeSlot::where('doctor_id', $doctor->id)
            ->where('id', '!=', $slot->id)
            ->where('clinic_id', $validated['clinic_id'])
            ->where('date', $validated['date'])
            ->where('start_time', $validated['start_time'])
            ->exists();

        if ($duplicate) {
            return back()->withErrors([
                'duplicate' => 'این نوبت قبلاً وجود دارد.',
            ]);
        }

        $slot->update($validated);

        return back()->with('success', 'نوبت بروزرسانی شد.');
    }

    /**
     * تغییر وضعیت نوبت
     */
    public function toggle(TimeSlot $slot)
    {
        $doctor = $this->currentDoctor();

        $this->authorizeSlot($slot, $doctor->id);

        if ($slot->status === 'booked') {
            return back()->withErrors([
                'error' => 'نوبت رزرو شده قابل لغو نیست.',
            ]);
        }

        $newStatus = $slot->status === 'available'
            ? 'cancelled'
            : 'available';

        $slot->update([
            'status' => $newStatus,
        ]);

        $message = $newStatus === 'available'
            ? 'نوبت فعال شد.'
            : 'نوبت لغو شد.';

        return back()->with('success', $message);
    }

    /**
     * حذف نوبت
     */
    public function destroy(TimeSlot $slot)
    {
        $doctor = $this->currentDoctor();

        $this->authorizeSlot($slot, $doctor->id);

        if ($slot->status === 'booked') {
            return back()->withErrors([
                'error' => 'نوبت رزرو شده قابل حذف نیست.',
            ]);
        }

        $slot->delete();

        return back()->with('success', 'نوبت حذف شد.');
    }

    /**
     * تولید گروهی نوبت‌ها بر اساس الگوی ساعات کاری
     */
    public function generateSlots(Request $request)
    {
        $doctor = $this->currentDoctor();

        $validated = $request->validate([
            'weeks_count' => 'required|integer|min:1|max:52',
            'clinic_id' => 'nullable|exists:addresses,id',
        ], [
            'weeks_count.required' => 'تعداد هفته‌ها الزامی است',
            'weeks_count.min' => 'حداقل ۱ هفته',
            'weeks_count.max' => 'حداکثر ۵۲ هفته (یک سال)',
        ]);

        if (!empty($validated['clinic_id'])) {
            $this->authorizeClinic(
                $doctor,
                (int) $validated['clinic_id']
            );
        }

        $weeksCount = (int) $validated['weeks_count'];

        $generatedCount = 0;
        $skippedCount = 0;

        $query = DoctorWorkingHour::where('doctor_id', $doctor->id)
            ->where('is_active', true);

        if (!empty($validated['clinic_id'])) {
            $query->where('clinic_id', $validated['clinic_id']);
        }

        $workingHours = $query->get();

        if ($workingHours->isEmpty()) {
            return back()->withErrors([
                'error' => 'هیچ الگوی شیفت فعالی برای تولید نوبت وجود ندارد. ابتدا شیفت‌ها را تعریف کنید.',
            ]);
        }

        // شروع هفته از شنبه
        $baseDate = Carbon::now()->startOfWeek(Carbon::SATURDAY);

        for ($week = 0; $week < $weeksCount; $week++) {

            $currentWeekStart = $baseDate->copy()->addWeeks($week);

            foreach ($workingHours as $wh) {

                // 0 = شنبه، 1 = یکشنبه، ...، 6 = جمعه
                $targetDate = $currentWeekStart
                    ->copy()
                    ->addDays($wh->day_of_week);

                // حذف روزهای گذشته؛ امروز مجاز است
                if ($targetDate->isBefore(Carbon::today())) {
                    continue;
                }

                $startTime = Carbon::parse(
                    $targetDate->format('Y-m-d') . ' ' . $wh->start_time
                );

                $endTime = Carbon::parse(
                    $targetDate->format('Y-m-d') . ' ' . $wh->end_time
                );

                $duration = (int) $wh->duration_minutes;

                $currentSlotTime = $startTime->copy();

                while (
                    $currentSlotTime->copy()
                        ->addMinutes($duration)
                        ->lte($endTime)
                ) {
                    $slotEndTime = $currentSlotTime
                        ->copy()
                        ->addMinutes($duration);

                    $exists = TimeSlot::where('doctor_id', $doctor->id)
                        ->where('clinic_id', $wh->clinic_id)
                        ->where('date', $targetDate->format('Y-m-d'))
                        ->where(
                            'start_time',
                            $currentSlotTime->format('H:i:s')
                        )
                        ->exists();

                    if (!$exists) {
                        TimeSlot::create([
                            'doctor_id' => $doctor->id,
                            'clinic_id' => $wh->clinic_id,
                            'date' => $targetDate->format('Y-m-d'),
                            'start_time' => $currentSlotTime->format('H:i:s'),
                            'end_time' => $slotEndTime->format('H:i:s'),
                            'status' => 'available',
                        ]);

                        $generatedCount++;
                    } else {
                        $skippedCount++;
                    }

                    $currentSlotTime->addMinutes($duration);
                }
            }
        }

        $message = "با موفقیت {$generatedCount} نوبت جدید برای {$weeksCount} هفته آینده ایجاد شد.";

        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} نوبت تکراری رد شد)";
        }

        return back()->with('success', $message);
    }
}
