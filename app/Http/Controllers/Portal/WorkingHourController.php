<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DoctorWorkingHour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
class WorkingHourController extends Controller
{
    use HasActiveDoctor;


    /**
     * نمایش ساعات کاری پزشک
     */
    public function index(Request $request)
    {
        $doctor = $this->currentDoctor();

        $clinics = $doctor->address()
            ->with('city')
            ->get();

        $query = DoctorWorkingHour::query()
            ->where('doctor_id', $doctor->id)
            ->with('clinic.city');

        if ($request->filled('clinic_id')) {
            $query->where('clinic_id', $request->input('clinic_id'));
        }

        if ($request->filled('is_active')) {
            $query->where(
                'is_active',
                $request->input('is_active') === 'true'
            );
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->whereHas('clinic', function ($clinicQuery) use ($search) {
                $clinicQuery->where('name', 'LIKE', "%{$search}%")
                    ->orWhereHas('city', function ($cityQuery) use ($search) {
                        $cityQuery->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        $allowedSortColumns = [
            'day_of_week',
            'start_time',
            'end_time',
            'duration_minutes',
            'is_active',
            'clinic_id',
            'created_at',
        ];

        $sortBy = $request->input('sort_by', 'day_of_week');

        if (!in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'day_of_week';
        }

        $sortDir = strtolower($request->input('sort_dir', 'asc'));

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $workingHours = $query
            ->orderBy($sortBy, $sortDir)
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Portal/WorkingHours', [
            'doctor' => $doctor,
            'clinics' => $clinics,
            'workingHours' => $workingHours,
            'filters' => $request->only([
                'clinic_id',
                'is_active',
                'search',
                'sort_by',
                'sort_dir',
            ]),
        ]);
    }

    /**
     * ایجاد بازه کاری جدید
     */
    public function store(Request $request)
    {
        $doctor = $this->currentDoctor();

        $validated = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:addresses,id'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
        ]);

        $clinicExists = $doctor->address()
            ->whereKey($validated['clinic_id'])
            ->exists();

        if (!$clinicExists) {
            return back()
                ->withErrors([
                    'clinic_id' => __('working_hours.invalid_clinic'),
                ])
                ->withInput();
        }

        $duplicate = $this->findOverlappingHour(
            $doctor->id,
            $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time']
        );

        if ($duplicate) {
            return back()
                ->withErrors([
                    'duplicate' => $this->overlapMessage($duplicate),
                ])
                ->withInput();
        }

        DoctorWorkingHour::create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $validated['clinic_id'],
            'day_of_week' => $validated['day_of_week'],
            'duration_minutes' => $validated['duration_minutes'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'is_active' => true,
        ]);

        return back()->with(
            'success',
            __('working_hours.created')
        );
    }

    /**
     * ویرایش بازه کاری
     */
    public function update(
        Request $request,
        DoctorWorkingHour $workingHour
    ) {
        $doctor = $this->currentDoctor();

        $this->authorizeOwnership($workingHour, $doctor->id);

        $validated = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:addresses,id'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
        ]);

        $clinicExists = $doctor->address()
            ->whereKey($validated['clinic_id'])
            ->exists();

        if (!$clinicExists) {
            return back()
                ->withErrors([
                    'clinic_id' => __('working_hours.invalid_clinic'),
                ])
                ->withInput();
        }

        $duplicate = $this->findOverlappingHour(
            $doctor->id,
            $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time'],
            $workingHour->id
        );

        if ($duplicate) {
            return back()
                ->withErrors([
                    'duplicate' => $this->overlapMessage($duplicate),
                ])
                ->withInput();
        }

        $workingHour->update($validated);

        return back()->with(
            'success',
            __('working_hours.updated')
        );
    }

    /**
     * فعال یا غیرفعال کردن بازه کاری
     */
    public function toggle(DoctorWorkingHour $workingHour)
    {
        $doctor = $this->currentDoctor();

        $this->authorizeOwnership($workingHour, $doctor->id);

        $workingHour->update([
            'is_active' => !$workingHour->is_active,
        ]);

        return back()->with(
            'success',
            __('working_hours.status_changed')
        );
    }

    /**
     * حذف بازه کاری
     */
    public function destroy(DoctorWorkingHour $workingHour)
    {
        $doctor = $this->currentDoctor();

        $this->authorizeOwnership($workingHour, $doctor->id);

        $workingHour->delete();

        return back()->with(
            'success',
            __('working_hours.deleted')
        );
    }

    /**
     * پیدا کردن بازه هم‌پوشان در همان روز
     */
    private function findOverlappingHour(
        int $doctorId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ): ?DoctorWorkingHour {
        $query = DoctorWorkingHour::query()
            ->where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->with('clinic');

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    /**
     * پیام بازه هم‌پوشان
     */
    private function overlapMessage(DoctorWorkingHour $duplicate): string
    {
        return __('working_hours.duplicate', [
            'day' => __('working_hours.days.' . $duplicate->day_of_week),
            'clinic' => $duplicate->clinic?->name
                ?? __('working_hours.unknown_clinic'),
        ]);
    }

    /**
     * بررسی مالکیت بازه کاری
     */
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
