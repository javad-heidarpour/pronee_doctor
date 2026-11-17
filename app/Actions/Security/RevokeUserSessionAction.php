<?php

namespace App\Actions\Security;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class RevokeUserSessionAction
{
    public function handle(User $user, string $sessionKey, string $currentSessionId): bool
    {
        if (config('session.driver') !== 'database' || ! preg_match('/^[a-f0-9]{64}$/', $sessionKey)) {
            return false;
        }

        $table = config('session.table', 'sessions');
        $connection = DB::connection(config('session.connection'));

        $sessions = $connection->table($table)
            ->where('user_id', $user->getAuthIdentifier())
            ->get(['id']);

        foreach ($sessions as $session) {
            $id = (string) $session->id;

            if (! hash_equals($currentSessionId, $id) && hash_equals(hash('sha256', $id), $sessionKey)) {
                return $connection->table($table)
                    ->where('id', $id)
                    ->where('user_id', $user->getAuthIdentifier())
                    ->delete() === 1;
            }
        }

        return false;
    }
}
