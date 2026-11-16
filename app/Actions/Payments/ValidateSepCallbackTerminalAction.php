<?php

namespace App\Actions\Payments;

class ValidateSepCallbackTerminalAction
{
    public function handle(string $callbackTerminalId): bool
    {
        $configuredTerminalId = trim(
            (string) config('services.sep.terminal_id', '')
        );

        return $configuredTerminalId !== ''
            && $callbackTerminalId !== ''
            && hash_equals($configuredTerminalId, $callbackTerminalId);
    }
}
