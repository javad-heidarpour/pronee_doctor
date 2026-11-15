<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    /**
     * نمایش فرم ورود
     */
    public function showLoginForm()
    {
        return Inertia::render('Portal/Login');
    }

    /**
     * ورود پزشک
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['is_active'] = true;

        if (
            !Auth::guard('web')->attempt(
                $credentials,
                $request->boolean('remember')
            )
        ) {
            throw ValidationException::withMessages([
                'email' => 'ایمیل یا رمز عبور اشتباه است.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::guard('web')->user();

        // مدیر سامانه: ورود بدون نیاز به اتصال پزشک
        if ($user->role === 'admin') {
            return redirect()->route('portal.dashboard');
        }

        // فقط پزشک و منشی مجاز به ادامه هستند
        if (!in_array($user->role, ['doctor', 'secretary'], true)) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'شما مجاز به ورود به این پنل نیستید.',
            ]);
        }

        // دریافت پزشکان متصل از جدول واسط
        $doctors = $user->doctors()
            ->where('doctors.is_active', true)
            ->get();

        if ($doctors->isEmpty()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'هیچ پرونده پزشک فعالی به حساب کاربری شما متصل نیست.',
            ]);
        }

        // بررسی مالکیت برای پزشک
        if ($user->role === 'doctor') {
            $hasOwnership = $doctors->contains(
                fn($doctor) => (int) $doctor->user_id === (int) $user->id
            );

            if (!$hasOwnership) {
                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => 'مالکیت پرونده پزشک برای این حساب تأیید نشد.',
                ]);
            }
        }

        return redirect()->route('portal.dashboard');
    }
    /**
     * خروج از پنل
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
