<?php

namespace App\Actions\Portal\Auth;

use App\Actions\Security\RecordSecurityAuditEventAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutPortalUserAction
{
    public function handle(
        Request $request,
        RecordSecurityAuditEventAction $audit,
        string $event = 'auth.logout'
    ): void
    {
        $user = Auth::guard('web')->user();

        if ($user) {
            $audit->handle($event, $user, [
                'role' => $user->role,
                'is_active' => (bool) $user->is_active,
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->forget('active_doctor_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
