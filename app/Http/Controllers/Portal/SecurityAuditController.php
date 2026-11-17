<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Security\ListSecurityAuditLogsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ListSecurityAuditLogsRequest;
use Inertia\Inertia;
use Inertia\Response;

class SecurityAuditController extends Controller
{
    public function index(
        ListSecurityAuditLogsRequest $request,
        ListSecurityAuditLogsAction $action
    ): Response {
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
