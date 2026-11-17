<?php

declare(strict_types=1);

namespace App\Contracts\Sms;

interface SmsGateway
{
    public function sendTemplate(
        string $phone,
        string $templateName,
        array $inputs,
        string $referenceId
    ): array;
}
