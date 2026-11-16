<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\SurgeryType;
use App\Models\TimeSlot;
use App\Models\VisitReason;
use App\Models\AppointmentPayment;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;




class PatientController extends Controller
{
    use HasActiveDoctor;



    public function index(Request $request)
    {
        $user = Auth::guard('web')->user();
        $doctor = $this->currentDoctor();

        $query = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->with([
                'user',
                'timeSlot',
                'clinic.city',
                'visitReason',
                'surgeryType',
                'latestPayment',
            ])
            ->withCount('payments')
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc');

        /*
        |--------------------------------------------------------------------------
        | فیلتر وضعیت نوبت
        |--------------------------------------------------------------------------
        | این فیلتر مربوط به appointments.status است، نه وضعیت پرداخت.
        */
        // فیلتر وضعیت نوبت
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فیلتر وضعیت پرداخت بر اساس آخرین پرداخت
        if ($request->filled('payment_status')) {
            $query->whereHas('latestPayment', function ($q) use ($request) {
                $q->where('status', $request->payment_status);
            });
        }
        /*
        |--------------------------------------------------------------------------
        | فیلتر تاریخ
        |--------------------------------------------------------------------------
        */
        if ($request->filled('date_from')) {
            $query->whereDate('appointment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('appointment_date', '<=', $request->date_to);
        }

        /*
        |--------------------------------------------------------------------------
        | جستجوی بیمار
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                    ->orWhere('patient_phone', 'like', "%{$search}%")
                    ->orWhere('patient_national_code', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | دریافت نوبت‌ها
        |--------------------------------------------------------------------------
        */
        $appointments = $query->paginate(20)->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | افزودن وضعیت پرداخت به خروجی فرانت
        |--------------------------------------------------------------------------
        | status اصلی نوبت دست‌نخورده باقی می‌ماند.
        | payment_status وضعیت آخرین پرداخت است.
        */
        $appointments->getCollection()->transform(function ($appointment) {

            $paymentStatus = $appointment->latestPayment?->status;

            $paymentLabels = [
                'pending' => 'در انتظار پرداخت',
                'paid' => 'پرداخت شده',
                'cancelled' => 'لغو شده',
                'failed' => 'پرداخت ناموفق',
                'refunded' => 'مبلغ مسترد شده',
            ];

            $appointment->payment_status = $paymentStatus;
            $appointment->payment_status_label = $paymentLabels[$paymentStatus]
                ?? ($paymentStatus ? 'نامشخص' : 'عملیات پرداخت شروع نشده');

            return $appointment;
        });

        /*
        |--------------------------------------------------------------------------
        | آمار وضعیت نوبت‌ها
        |--------------------------------------------------------------------------
        */
        $stats = [
            'total' => Appointment::where('doctor_id', $doctor->id)->count(),

            'pending' => Appointment::where('doctor_id', $doctor->id)
                ->where('status', 'pending')
                ->count(),

            'confirmed' => Appointment::where('doctor_id', $doctor->id)
                ->where('status', 'confirmed')
                ->count(),

            'cancelled' => Appointment::where('doctor_id', $doctor->id)
                ->where('status', 'cancelled')
                ->count(),

            'visited' => Appointment::where('doctor_id', $doctor->id)
                ->where('status', 'visited')
                ->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | اطلاعات مورد نیاز فرم‌ها
        |--------------------------------------------------------------------------
        */
        $clinics = $doctor->address()
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
            'appointments' => $appointments,
            'stats' => $stats,
            'clinics' => $clinics,
            'visitReasons' => $visitReasons,
            'surgeryTypes' => $surgeryTypes,


            'filters' => $request->only([
                'status',
                'payment_status',
                'date_from',
                'date_to',
                'search',
            ]),
        ]);
    }

    /**
     * دریافت تاریخ‌هایی که در یک مطب زمان آزاد دارند.
     */
    public function availableDates(Request $request)
    {
        $validated = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:addresses,id'],
        ]);

        $doctor = $this->currentDoctor();

        // اطمینان از اینکه مطب متعلق به پزشک فعال است.
        $clinicExists = $doctor->address()
            ->where('addresses.id', $validated['clinic_id'])
            ->exists();

        abort_unless($clinicExists, 403);

        $dates = TimeSlot::query()
            ->where('doctor_id', $doctor->id)
            ->where('clinic_id', $validated['clinic_id'])
            ->where('status', 'available')
            ->whereDate('date', '>=', now()->toDateString())
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('appointments')
                    ->whereColumn('appointments.time_slot_id', 'time_slots.id')
                    ->whereNotIn('appointments.status', ['cancelled']);
            })
            ->distinct()
            ->orderBy('date')
            ->pluck('date')
            ->map(fn($date) => (string) $date)
            ->values();

        return response()->json([
            'success' => true,
            'dates' => $dates,
        ]);
    }


    /**
     * دریافت ساعت‌های آزاد یک مطب در یک تاریخ مشخص.
     */

    public function availableSlots(Request $request)
    {
        $validated = $request->validate([
            'clinic_id' => ['required', 'integer'],
        ]);

        $doctor = $this->currentDoctor();

        // بررسی تعلق مطب به پزشک فعال
        $clinicExists = $doctor->address()
            ->where('addresses.id', $validated['clinic_id'])
            ->exists();

        if (!$clinicExists) {
            return response()->json([
                'message' => 'کلینیک انتخاب‌شده معتبر نیست.',
            ], 422);
        }

        $slots = TimeSlot::query()
            ->where('doctor_id', $doctor->id)
            ->where('clinic_id', $validated['clinic_id'])
            ->where('status', 'available')
            ->whereDate('date', '>=', now()->toDateString())
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('appointments')
                    ->whereColumn(
                        'appointments.time_slot_id',
                        'time_slots.id'
                    )
                    ->whereNotIn('appointments.status', ['cancelled']);
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get([
                'id',
                'clinic_id',
                'date',
                'start_time',
                'end_time',
            ]);

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

    public function updateStatus(Request $request, Appointment $appointment)
    {
        $this->authorizeAppointment($appointment);

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,paid,cancelled,visited',
        ]);

        // نوبت ویزیت‌شده قابل تغییر نیست
        if ($appointment->status === 'visited') {
            return back()->with(
                'error',
                'وضعیت این نوبت قبلاً به ویزیت‌شده تغییر کرده و امکان تغییر مجدد آن وجود ندارد.'
            );
        }

        // جلوگیری از تغییر مجدد به وضعیت visited از این متد
        if ($validated['status'] === 'visited') {
            return back()->with(
                'error',
                'ثبت وضعیت ویزیت‌شده از این بخش امکان‌پذیر نیست.'
            );
        }

        try {

            DB::transaction(function () use ($appointment, $validated) {

                // قفل کردن نوبت
                $appointment = Appointment::whereKey($appointment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // قفل کردن ساعت مربوطه
                $timeSlot = TimeSlot::whereKey($appointment->time_slot_id)
                    ->lockForUpdate()
                    ->first();

                $newStatus = $validated['status'];

                // بررسی اشغال بودن ساعت در صورت فعال‌سازی مجدد نوبت
                if (in_array($newStatus, ['pending', 'confirmed'])) {

                    $anotherAppointmentExists = Appointment::where(
                        'time_slot_id',
                        $appointment->time_slot_id
                    )
                        ->where('id', '!=', $appointment->id)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->exists();

                    if ($anotherAppointmentExists) {
                        throw ValidationException::withMessages([
                            'status' => 'امکان فعال‌سازی مجدد این نوبت وجود ندارد؛ زیرا ساعت انتخاب‌شده توسط نوبت دیگری رزرو شده است. لطفاً ساعت دیگری انتخاب کنید.',
                        ]);
                    }
                }

                // تغییر وضعیت نوبت
                $appointment->update([
                    'status' => $newStatus,
                ]);

                if ($timeSlot) {

                    if ($newStatus === 'cancelled') {

                        $hasActiveAppointment = Appointment::where(
                            'time_slot_id',
                            $timeSlot->id
                        )
                            ->whereIn('status', ['pending', 'confirmed'])
                            ->exists();

                        if (!$hasActiveAppointment) {
                            $timeSlot->update([
                                'status' => 'available',
                            ]);
                        }

                    } elseif (in_array($newStatus, ['pending', 'confirmed'])) {

                        $timeSlot->update([
                            'status' => 'booked',
                        ]);
                    }
                }
            });

            return back()->with(
                'success',
                'وضعیت نوبت با موفقیت تغییر کرد.'
            );

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
            (int) $appointment->doctor_id === (int) $doctor->id,
            403,
            'شما اجازه دسترسی به این نوبت را ندارید.'
        );

        return true;
    }

    public function updateDescription(Request $request, Appointment $appointment)
    {
        $this->authorizeAppointment($appointment);

        // جلوگیری از ویرایش نوبت لغوشده
        if (in_array($appointment->status, ['cancelled', 'canceled'], true)) {
            return back()->with(
                'error',
                'امکان ثبت یا ویرایش اطلاعات نوبت لغوشده وجود ندارد.'
            );
        }

        $validated = $request->validate([
            'description' => 'nullable|string|max:1000',
            'surgery_type_id' => 'nullable|exists:surgery_types,id',
            'new_surgery_type' => 'nullable|string|max:255',
            'surgery_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($appointment, $validated) {

            $newSurgeryType = trim($validated['new_surgery_type'] ?? '');

            // نوع جراحی جدید
            if ($newSurgeryType !== '') {
                $surgeryType = SurgeryType::firstOrCreate([
                    'title' => $newSurgeryType,
                ]);

                $validated['surgery_type_id'] = $surgeryType->id;
            }

            unset($validated['new_surgery_type']);

            $appointment->update([
                'surgery_type_id' => $validated['surgery_type_id'] ?? null,
                'surgery_date' => $validated['surgery_date'] ?? null,
                'description' => $validated['description'] ?? null,

                'status' => 'visited',
            ]);
        });

        return back()->with('success', 'اطلاعات نوبت با موفقیت ذخیره شد.');
    }
}