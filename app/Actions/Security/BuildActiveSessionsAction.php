<?php

namespace App\Actions\Security;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class BuildActiveSessionsAction
{
    public function handle(User $user, string $currentSessionId): array
    {
        if (config('session.driver') !== 'database') {
            return [
                'supported' => false,
                'sessions' => [],
                'message' => 'مدیریت نشست‌ها فقط با درایور database فعال است.',
            ];
        }

        $table = config('session.table', 'sessions');
        $cutoff = now()->subMinutes((int) config('session.lifetime', 120))->timestamp;

        $sessions = DB::connection(config('session.connection'))
            ->table($table)
            ->where('user_id', $user->getAuthIdentifier())
            ->where('last_activity', '>=', $cutoff)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity']);

        return [
            'supported' => true,
            'message' => null,
            'sessions' => $sessions->map(function ($session) use ($currentSessionId) {
                $agent = (string) $session->user_agent;

                return [
                    'key' => hash('sha256', $session->id),
                    'is_current' => hash_equals($currentSessionId, (string) $session->id),
                    'ip_address' => $session->ip_address,
                    'device' => $this->deviceLabel($agent),
                    'browser' => $this->browserLabel($agent),
                    'last_activity' => (int) $session->last_activity,
                ];
            })->values()->all(),
        ];
    }

    private function deviceLabel(string $agent): string
    {
        if (preg_match('/iPad|Tablet/i', $agent)) {
            return 'تبلت';
        }

        if (preg_match('/Mobile|Android|iPhone/i', $agent)) {
            return 'موبایل';
        }

        return 'رایانه';
    }

    private function browserLabel(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'Edg/') => 'Microsoft Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Mozilla Firefox',
            str_contains($agent, 'Chrome/') && ! str_contains($agent, 'Edg/') => 'Google Chrome',
            str_contains($agent, 'Safari/') && ! str_contains($agent, 'Chrome/') => 'Safari',
            default => 'مرورگر ناشناس',
        };
    }
}
