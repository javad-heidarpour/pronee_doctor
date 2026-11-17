<?php

namespace App\Actions\Auth;

use App\Services\LoginOtp;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendDoctorRegistrationPasswordAction
{
    public function __construct(
        private readonly LoginOtp $loginOtp
    ) {
    }

    public function handle(string $phone, string $temporaryPassword, int $doctorId): bool
    {
        try {
            $this->loginOtp->sendTemplate(
                $phone,
                'DoctorPassword',
                [
                    [
                        'param' => 'param1',
                        'value' => $temporaryPassword,
                    ],
                ],
                'doctor-password-' . $doctorId
            );

            return true;
        } catch (Throwable $exception) {
            Log::error('Doctor password SMS failed', [
                'doctor_id' => $doctorId,
                'exception_class' => get_class($exception),
            ]);

            return false;
        }
    }
}
