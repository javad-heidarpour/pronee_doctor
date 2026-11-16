<?php

namespace App\Actions\Auth;

use App\Services\LoginOtp;

class SendDoctorRegistrationOtpAction
{
    public function __construct(
        private readonly LoginOtp $loginOtp
    ) {
    }

    public function handle(string $phone): void
    {
        $this->loginOtp->send($phone);
    }
}
