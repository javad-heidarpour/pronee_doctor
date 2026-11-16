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

        abort_unless(
            $assignment,
            403,
            'به پرونده پزشک انتخاب‌شده دسترسی ندارید.'
        );

        $routeName = (string) $request->route()?->getName();
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

        abort_unless(
            is_array($permissions) && in_array($permission, $permissions, true),
            403,
            'مجوز انجام این عملیات برای شما فعال نیست.'
        );

        return $next($request);
    }

    private function requiredPermission(string $routeName, string $method): ?string
    {
        $map = [
            'portal.patients.' => 'appointments',
            'portal.appointments.' => 'appointments',
            'portal.clinics.' => 'clinics',
            'portal.shifts.' => 'shifts',
            'portal.working-hours.' => 'working-hours',
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