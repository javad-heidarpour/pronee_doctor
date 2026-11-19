<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Security\ListSecurityAuditLogsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ListSecurityAuditLogsRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SecurityAuditController extends Controller
{
    public function index(
        ListSecurityAuditLogsRequest $request,
        ListSecurityAuditLogsAction $action
    ): Response {
        // گزارش امنیتی ممکن است رویدادها و شناسه‌های کاربران دیگر را در بر بگیرد؛
        // بنابراین مشاهده آن فقط باید در اختیار مدیر سامانه باشد.
        abort_unless(Auth::guard('web')->user()?->role === 'admin', 403);

        $filters = $request->validated();
        $data = $action->handle($filters);

        return Inertia::render('Portal/Security/AuditLogs', [
            ...$data,
            'filters' => [
                'event' => $filters['event'] ?? '',
                'actor_id' => $filters['actor_id'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
            ],
        ]);
    }
}
