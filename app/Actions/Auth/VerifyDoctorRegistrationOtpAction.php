<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\LoginOtp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VerifyDoctorRegistrationOtpAction
{
    public function __construct(
        private readonly LoginOtp $loginOtp
    ) {
    }

    /**
     * Returns one of: already_registered, invalid_code, verified.
     */
    public function handle(string $phone, string $code): array
    {
        if (User::where('phone', $phone)->exists()) {
            return ['status' => 'already_registered'];
        }

        if (! $this->loginOtp->verify($phone, $code)) {
            return ['status' => 'invalid_code'];
        }

        $token = Str::random(64);

        Cache::put(
            $this->registrationTokenKey($token),
            $phone,
            now()->addMinutes(10)
        );

        return [
            'status' => 'verified',
            'registration_token' => $token,
        ];
    }

    private function registrationTokenKey(string $token): string
    {
        return 'doctor-registration-token:' . hash('sha256', $token);
    }
}
