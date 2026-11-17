<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Security\BuildActiveSessionsAction;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Actions\Security\RevokeUserSessionAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecuritySessionsController extends Controller
{
    public function index(Request $request, BuildActiveSessionsAction $action): Response
    {
        $user = $request->user('web');

        abort_unless($user, 401);

        return Inertia::render('Portal/Security/Sessions', [
            'sessionData' => $action->handle($user, $request->session()->getId()),
        ]);
    }

    public function destroy(
        Request $request,
        string $session,
        RevokeUserSessionAction $action,
        RecordSecurityAuditEventAction $audit
    ): RedirectResponse {
        $user = $request->user('web');

        abort_unless($user, 401);

        $revoked = $action->handle($user, $session, $request->session()->getId());

        abort_unless($revoked, 404);

        $audit->handle('security.session_revoked', $user, [
            'role' => $user->role,
        ]);

        return back()->with('success', 'نشست انتخاب‌شده با موفقیت خاتمه یافت.');
    }
}
