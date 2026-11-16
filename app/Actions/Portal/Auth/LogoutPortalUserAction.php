<?php

namespace App\Actions\Portal\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutPortalUserAction
{
    public function handle(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->forget('active_doctor_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
