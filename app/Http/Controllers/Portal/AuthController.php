<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('Portal/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['is_active'] = true;

        if (!Auth::guard('web')->attempt(
            $credentials,
            $request->boolean('remember')
        )) {
            throw ValidationException::withMessages([
                'email' => 'ایمیل یا رمز عبور اشتباه است.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->forget('active_doctor_id');
        $user = Auth::guard('web')->user();

        if (!in_array($user->role, ['admin', 'doctor', 'secretary'], true)) {
            $this->logoutAndInvalidate($request);

            throw ValidationException::withMessages([
                'email' => 'شما مجاز به ورود به این پنل نیستید.',
            ]);
        }

        // مدیر برای ورود به داشبورد به پرونده پزشک متصل نیاز ندارد.
        if ($user->role !== 'admin') {
            $doctor = $user->accessibleDoctors()->first();

            if (!$doctor) {
                $this->logoutAndInvalidate($request);

                throw ValidationException::withMessages([
                    'email' => 'هیچ پرونده پزشک فعالی به این حساب کاربری متصل نیست.',
                ]);
            }

            // انتخاب خودکار فقط زمانی که یک پرونده قابل دسترس وجود دارد.
            if ($user->accessibleDoctors()->count() === 1) {
                session(['active_doctor_id' => $doctor->id]);
            } else {
                session()->forget('active_doctor_id');
            }
        }

        return redirect()->intended(route('portal.dashboard'));
    }

    public function logout(Request $request)
    {
        $this->logoutAndInvalidate($request);

        return redirect()->route('portal.login');
    }

    private function logoutAndInvalidate(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->forget('active_doctor_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}