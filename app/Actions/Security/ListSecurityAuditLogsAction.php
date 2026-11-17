<?php

namespace App\Actions\Security;

use App\Models\SecurityAuditLog;

class ListSecurityAuditLogsAction
{
    public function handle(array $filters): array
    {
        $logs = SecurityAuditLog::query()
            ->with('actor:id,name,family,role')
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
            ->when($filters['actor_id'] ?? null, fn ($query, $actorId) => $query->where('actor_id', $actorId))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
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

        return [
            'logs' => $logs,
            'events' => SecurityAuditLog::query()
                ->select('event')
                ->distinct()
                ->orderBy('event')
                ->pluck('event'),
        ];
    }
}
