<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Auth\AuthenticatePortalUserAction;
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
        LogoutPortalUserAction $logoutAction
    ) {
        $action->handle($request, $logoutAction);

        return redirect()->intended(route('portal.dashboard'));
    }

    public function logout(
        Request $request,
        LogoutPortalUserAction $action
    ) {
        $action->handle($request);

        return redirect()->route('portal.login');
    }
}
