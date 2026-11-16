<?php

namespace App\Actions\Portal\Auth;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Requests\Portal\PortalLoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatePortalUserAction
{
    public function handle(
        PortalLoginRequest $request,
        LogoutPortalUserAction $logoutAction,
        RecordSecurityAuditEventAction $audit
    ): User {
        $credentials = $request->safe()->only(['email', 'password']);
        $credentials['is_active'] = true;

        if (! Auth::guard('web')->attempt(
            $credentials,
            $request->boolean('remember')
        )) {
            $audit->handle('auth.login_failed');

            throw ValidationException::withMessages([
                'email' => 'ایمیل یا رمز عبور اشتباه است.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->forget('active_doctor_id');

        /** @var User $user */
        $user = Auth::guard('web')->user();

        if (! in_array($user->role, ['admin', 'doctor', 'secretary'], true)) {
            $logoutAction->handle($request, $audit);

            throw ValidationException::withMessages([
                'email' => 'شما مجاز به ورود به این پنل نیستید.',
            ]);
        }

        if ($user->role !== 'admin') {
            $doctor = $user->accessibleDoctors()->first();

            if (! $doctor) {
                $logoutAction->handle($request);

                throw ValidationException::withMessages([
                    'email' => 'هیچ پرونده پزشک فعالی به این حساب کاربری متصل نیست.',
                ]);
            }

            if ($user->accessibleDoctors()->count() === 1) {
                $request->session()->put('active_doctor_id', $doctor->id);
            } else {
                $request->session()->forget('active_doctor_id');
            }
        }

        $audit->handle('auth.login_succeeded', $user, [
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
        ]);

        return $user;
    }
}
