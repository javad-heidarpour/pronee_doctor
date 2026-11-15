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
        $user = Auth::guard('web')->user();

        if (!$user) {
            return redirect()->route('portal.login');
        }

        // بررسی فعال بودن حساب
        if (!$user->is_active) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('portal.login')
                ->withErrors([
                    'email' => 'حساب کاربری شما غیرفعال است.',
                ]);
        }

        // نقش‌های مجاز برای ورود به پنل
        if (!in_array($user->role, ['admin', 'doctor', 'secretary'], true)) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('portal.login')
                ->withErrors([
                    'email' => 'شما مجاز به ورود به این پنل نیستید.',
                ]);
        }

        return $next($request);
    }
}