<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function selectDoctor(Request $request, int $doctor)
    {
        $user = Auth::guard('web')->user();

        abort_unless($user, 401);

        // فقط پزشکانی که از طریق doctor_user متصل هستند
        $hasAccess = $user->doctors()
            ->where('doctors.id', $doctor)
            ->where('doctors.is_active', true)
            ->exists();

        abort_unless($hasAccess, 403, 'دسترسی به این پزشک مجاز نیست.');

        session(['active_doctor_id' => $doctor]);

        return redirect()->route('portal.dashboard');
    }
    public function index(Request $request)
    {
        $user = Auth::guard('web')->user();

        abort_unless($user, 401);

        // دریافت پزشکان قابل دسترسی بر اساس نقش کاربر
        if ($user->role === 'admin') {

            // ادمین به تمام پزشکان فعال دسترسی دارد
            $doctors = \App\Models\Doctor::query()
                ->where('is_active', true)
                ->get();

        } else {

            // پزشک و منشی فقط پزشکان متصل‌شده در doctor_user را می‌بینند
            $doctors = $user->doctors()
                ->where('doctors.is_active', true)
                ->get();
        }

        // پزشک فعال از Session
        $activeDoctorId = session('active_doctor_id');

        // اگر پزشک انتخاب‌شده دیگر در دسترس نیست
        if (
            $activeDoctorId &&
            !$doctors->contains('id', (int) $activeDoctorId)
        ) {
            session()->forget('active_doctor_id');
            $activeDoctorId = null;
        }

        // اگر فقط یک پزشک متصل است، انتخاب خودکار
        if (!$activeDoctorId && $doctors->count() === 1) {
            $activeDoctorId = $doctors->first()->id;

            session(['active_doctor_id' => $activeDoctorId]);
        }

        // اگر هیچ پزشکی در دسترس نیست
        if ($doctors->isEmpty()) {
            abort(403, 'هیچ پرونده پزشکی برای این حساب کاربری تعریف نشده است.');
        }

        // اگر چند پزشک وجود دارد و هنوز انتخاب نشده
        if (!$activeDoctorId) {
            return Inertia::render('Portal/SelectDoctor', [
                'doctors' => $doctors->map(fn($doctor) => [
                    'id' => $doctor->id,
                    'name' => $doctor->name,
                    'family' => $doctor->family,
                    'caption' => $doctor->caption,
                    'photo' => $doctor->photo,
                    'specialty_title' => $doctor->specialty_title,
                ])->values(),
            ]);
        }

        // دریافت پزشک فعال
        $doctor = $doctors->firstWhere('id', (int) $activeDoctorId);

        abort_unless($doctor, 403, 'دسترسی به پزشک انتخاب‌شده مجاز نیست.');

        // آمار مربوط به پزشک فعال
        $stats = [
            'total_appointments' => $doctor->timeSlots()
                ->where('status', 'booked')
                ->count(),

            'pending_appointments' => $doctor->timeSlots()
                ->where('status', 'booked')
                ->whereHas(
                    'appointment',
                    fn($q) => $q->where('status', 'pending')
                )
                ->count(),

            'total_reviews' => $doctor->reviews()
                ->approved()
                ->count(),

            'average_rating' => round(
                $doctor->reviews()->approved()->avg('rating') ?? 0,
                1
            ),

            'total_images' => $doctor->images()
                ->active()
                ->count(),

            'total_clinics' => $doctor->address()->count(),
        ];

        return Inertia::render('Portal/Dashboard', [
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'family' => $doctor->family,
                'caption' => $doctor->caption,
                'photo' => $doctor->photo,
                'specialty_title' => $doctor->specialty_title,
                'email' => $user->email,
            ],

            'stats' => $stats,

            'doctors' => $doctors->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'family' => $item->family,
                'photo' => $item->photo,
                'specialty_title' => $item->specialty_title,
            ])->values(),

            'activeDoctorId' => (int) $activeDoctorId,
        ]);
    }
}
