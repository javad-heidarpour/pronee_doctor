<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\SecurityAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SecurityAuditController extends Controller
{
    /**
     * گزارش ممیزی فقط برای مدیر سیستم قابل مشاهده است.
     */
    public function index(Request $request): Response
    {
        abort_unless(Auth::guard('web')->user()?->role === 'admin', 403);

        $filters = $request->validate([
            'event' => ['nullable', 'string', 'max:100'],
            'actor_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $query = SecurityAuditLog::query()
            ->with('actor:id,name,family,role')
            ->when($filters['event'] ?? null, fn ($q, $event) => $q->where('event', $event))
            ->when($filters['actor_id'] ?? null, fn ($q, $actorId) => $q->where('actor_id', $actorId))
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));

        $logs = $query
            ->latest('id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (SecurityAuditLog $log) => [
                'id' => $log->id,
                'event' => $log->event,
                'actor' => $log->actor ? [
                    'id' => $log->actor->id,
                    'name' => trim($log->actor->name . ' ' . $log->actor->family),
                    'role' => $log->actor->role,
                ] : null,
                'actor_id' => $log->actor_id,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'metadata' => $log->metadata,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Portal/Security/AuditLogs', [
            'logs' => $logs,
            'filters' => [
                'event' => $filters['event'] ?? '',
                'actor_id' => $filters['actor_id'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
            ],
            'events' => SecurityAuditLog::query()
                ->select('event')
                ->distinct()
                ->orderBy('event')
                ->pluck('event'),
        ]);
    }
}
