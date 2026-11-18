<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DoctorAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('web');
        $user = $guard->user();

        if (!$user) {
            return redirect()->route('portal.login');
        }

        if (!$user->is_active) {
            $this->logout($request);

            return redirect()->route('portal.login')->withErrors([
                'email' => 'حساب کاربری شما غیرفعال است.',
            ]);
        }

        if (!in_array($user->role, ['admin', 'doctor', 'secretary'], true)) {
            $this->logout($request);

            return redirect()->route('portal.login')->withErrors([
                'email' => 'شما مجاز به ورود به این پنل نیستید.',
            ]);
        }

        // انتخاب پزشک باید پس از احراز هویت و بررسی فعال بودن حساب مجاز باشد،
        // اما نباید به انتخاب قبلی پزشک وابسته شود.
        if ($request->routeIs('portal.select-doctor')) {
            return $next($request);
        }

        // مدیریت نشست‌ها به انتخاب پزشک یا مجوزهای عملیاتی منشی وابسته نیست.
        if ($request->routeIs('portal.security.sessions', 'portal.security.sessions.destroy')) {
            return $next($request);
        }

        // پزشک و مدیر در این مرحله محدودیت دسترسی منشی را ندارند.
        if ($user->role !== 'secretary') {
            return $next($request);
        }

        $assignedDoctors = $user->doctors()
            ->where('doctors.is_active', true);

        $doctorId = session('active_doctor_id');

        if (!$doctorId) {
            $count = (clone $assignedDoctors)->count();

            if ($count === 0) {
                abort(403, 'هیچ پزشک فعالی به حساب منشی متصل نیست.');
            }

            if ($count === 1) {
                $doctorId = (clone $assignedDoctors)->value('doctors.id');
                session(['active_doctor_id' => $doctorId]);
            } else {
                // منشی ابتدا باید در داشبورد پزشک مورد نظر را انتخاب کند.
                if ($request->routeIs('portal.dashboard')) {
                    return $next($request);
                }

                return redirect()->route('portal.dashboard');
            }
        }

        $assignment = (clone $assignedDoctors)
            ->where('doctors.id', $doctorId)
            ->first();

        if (! $assignment) {
            // ممکن است پزشک انتخاب‌شده غیرفعال یا تخصیص منشی حذف شده باشد.
            // شناسه قدیمی نباید باعث قفل شدن پنل یا دسترسی به پزشک قبلی شود.
            session()->forget('active_doctor_id');

            return redirect()
                ->route('portal.dashboard')
                ->withErrors([
                    'doctor' => 'دسترسی قبلی شما تغییر کرده است؛ لطفاً پزشک فعال را دوباره انتخاب کنید.',
                ]);
        }

        $routeName = (string) $request->route()?->getName();

        if (in_array($routeName, ['portal.dashboard', 'portal.logout'], true)) {
            return $next($request);
        }

        $permission = $this->requiredPermission($routeName, $request->method());

        // بخش‌هایی که در فهرست مجوزهای منشی تعریف نشده‌اند، مخصوص پزشک/مدیر هستند.
        abort_unless(
            $permission !== null,
            403,
            'دسترسی به این بخش برای منشی تعریف نشده است.'
        );

        $permissions = $assignment->pivot?->permissions ?? [];

        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        $hasPermission = is_array($permissions) && in_array($permission, $permissions, true);
        if (!$hasPermission && $permission === 'patients.clinical.view') {
            $hasPermission = is_array($permissions) && in_array('patients.clinical.manage', $permissions, true);
        }

        abort_unless(
            $hasPermission,
            403,
            'مجوز انجام این عملیات برای شما فعال نیست.'
        );

        return $next($request);
    }

    private function requiredPermission(string $routeName, string $method): ?string
    {
        // صفحه بیماران در این پنل، فهرست نوبت‌ها را نمایش می‌دهد؛
        // بنابراین مشاهده آن باید با مجوز مشاهده نوبت‌ها کنترل شود.
        if ($routeName === 'portal.patients.index') {
            return 'appointments.view';
        }

        // ثبت نوبت از فرم بیماران، از نظر سطح دسترسی یک عملیات نوبت‌دهی است؛
        // نه مدیریت اطلاعات پایه بیمار.
        if ($routeName === 'portal.patients.store') {
            return 'appointments.manage';
        }

        // دریافت تقویم و ساعت‌های خالی، بخشی از مدیریت شیفت‌هاست؛
        // با مجوز shifts.view کنترل می‌شود تا با مجوز مشاهده نوبت‌ها اشتباه نشود.
        if (in_array($routeName, [
            'portal.patients.available-dates',
            'portal.patients.available-slots',
        ], true)) {
            return 'shifts.view';
        }

        if ($routeName === 'portal.encounters.create') {
            return 'patients.clinical.manage';
        }

        $specific = [
            'portal.operations.index' => 'services.view',
            'portal.operations.services.store' => 'services.manage',
            'portal.operations.treatment-items.store' => 'patients.clinical.manage',
            'portal.operations.treatment-items.update' => 'patients.clinical.manage',
            'portal.operations.dental-chart.store' => 'patients.clinical.manage',
            'portal.operations.inventory.store' => 'inventory.manage',
            'portal.operations.inventory.movements.store' => 'inventory.manage',
            'portal.operations.settlements.store' => 'finance.manage',
            'portal.operations.settlements.approve' => 'finance.manage',
            'portal.operations.settlements.pay' => 'finance.manage',
            'portal.operations.payrolls.store' => 'finance.manage',
            'portal.operations.payrolls.approve' => 'finance.manage',
            'portal.operations.payrolls.pay' => 'finance.manage',
        ];
        if (isset($specific[$routeName])) {
            return $specific[$routeName];
        }

        $map = [
            'portal.patients.' => 'patients',
            'portal.follow-ups.' => 'patients',
            'portal.encounters.' => 'patients.clinical',
            'portal.appointments.' => 'appointments',
            'portal.clinics.' => 'clinics',
            'portal.shifts.' => 'shifts',
            'portal.working-hours.' => 'working-hours',
            'portal.finance.' => 'finance',
        ];

        foreach ($map as $prefix => $resource) {
            if (str_starts_with($routeName, $prefix)) {
                $action = in_array(strtoupper($method), ['GET', 'HEAD'], true)
                    ? 'view'
                    : 'manage';

                return $resource . '.' . $action;
            }
        }

        return null;
    }

    private function logout(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->forget('active_doctor_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}