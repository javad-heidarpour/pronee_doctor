<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Validation\ValidationException;

final class MedicalReferralWorkflow
{
    public function allowedTransitions(string $status, bool $isSender, bool $isRecipient): array
    {
        if ($isSender && !$isRecipient) {
            return in_array($status, ['sent', 'accepted', 'scheduled', 'in_progress'], true) ? ['cancelled'] : [];
        }
        if (!$isRecipient || $isSender) {
            return [];
        }
        return match ($status) {
            'sent' => ['accepted', 'rejected'],
            'accepted' => ['scheduled', 'in_progress', 'rejected'],
            'scheduled' => ['in_progress', 'completed', 'rejected'],
            'in_progress' => ['completed', 'rejected'],
            default => [],
        };
    }

    public function assertCanTransition(string $status, string $next, bool $isSender, bool $isRecipient): void
    {
        if (!in_array($next, $this->allowedTransitions($status, $isSender, $isRecipient), true)) {
            throw ValidationException::withMessages([
                'status' => 'تغییر وضعیت انتخاب‌شده با نقش شما یا وضعیت فعلی ارجاع سازگار نیست.',
            ]);
        }
    }
}
