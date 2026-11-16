<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\SecurityAuditLog;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SecurityAuditController extends Controller
{
    /**
     * گزارش ممیزی فقط برای مدیر سیستم قابل مشاهده است.
     */
    public function index(): Response
    {
        abort_unless(Auth::guard('web')->user()?->role === 'admin', 403);

        $logs = SecurityAuditLog::query()
            ->with('actor:id,name,family,role')
            ->latest('id')
            ->paginate(30)
            ->through(fn (SecurityAuditLog $log) => [
                'id' => $log->id,
                'event' => $log->event,
                'actor' => $log->actor ? [
                    'name' => trim($log->actor->name . ' ' . $log->actor->family),
                    'role' => $log->actor->role,
                ] : null,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'metadata' => $log->metadata,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Portal/Security/AuditLogs', [
            'logs' => $logs,
        ]);
    }
}
