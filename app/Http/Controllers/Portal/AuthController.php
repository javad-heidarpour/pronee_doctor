<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Auth\AuthenticatePortalUserAction;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Actions\Portal\Auth\LogoutPortalUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\PortalLoginRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('Portal/Login');
    }

    public function login(
        PortalLoginRequest $request,
        AuthenticatePortalUserAction $action,
        LogoutPortalUserAction $logoutAction,
        RecordSecurityAuditEventAction $audit
    ) {
        $user = $action->handle($request, $logoutAction, $audit);

        return redirect()->intended(
            $user->role === 'user'
                ? route('portal.professional-partnerships.index')
                : route('portal.dashboard')
        );
    }

    public function logout(
        Request $request,
        LogoutPortalUserAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $action->handle($request, $audit);

        return redirect()->route('portal.login');
    }
}
