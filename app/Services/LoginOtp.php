<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LoginOtp
{
    private string $endpoint =
        'https://gateway.ghasedak.me/rest/api/v1/WebService/SendOtpSMS';

    private function otpKey(string $phone): string
    {
        return 'doctor-register-otp:' . $phone;
    }

    private function lockKey(string $phone): string
    {
        return 'doctor-register-otp-lock:' . $phone;
    }

    private function attemptsKey(string $phone): string
    {
        return 'doctor-register-otp-attempts:' . $phone;
    }

    /**
     * ارسال کد تأیید ثبت‌نام
     */
    public function send(string $phone): void
    {
        if (Cache::has($this->lockKey($phone))) {
            throw new RuntimeException(
                'کد تأیید قبلاً ارسال شده است. لطفاً دو دقیقه صبر کنید.'
            );
        }

        $code = (string) random_int(100000, 999999);

        // جلوگیری از ارسال هم‌زمان درخواست‌های تکراری
        if (!Cache::add($this->lockKey($phone), true, now()->addMinutes(2))) {
            throw new RuntimeException(
                'کد تأیید قبلاً ارسال شده است. لطفاً دو دقیقه صبر کنید.'
            );
        }

        Cache::put(
            $this->otpKey($phone),
            Hash::make($code),
            now()->addMinutes(5)
        );

        Cache::forget($this->attemptsKey($phone));

        try {
            $this->sendTemplate(
                $phone,
                'DoctorRegister',
                [
                    ['param' => 'Code', 'value' => $code],
                ],
                'doctor-register-' . $phone
            );
        } catch (\Throwable $e) {
            Cache::forget($this->otpKey($phone));
            Cache::forget($this->lockKey($phone));

            throw $e;
        }
    }

    /**
     * بررسی کد تأیید
     */
    public function verify(string $phone, string $code): bool
    {
        $otpHash = Cache::get($this->otpKey($phone));

        if (!$otpHash) {
            return false;
        }

        $attempts = (int) Cache::get($this->attemptsKey($phone), 0);

        if ($attempts >= 5) {
            Cache::forget($this->otpKey($phone));

            throw new RuntimeException(
                'تعداد تلاش‌های شما بیش از حد مجاز است. دوباره درخواست کد بدهید.'
            );
        }

        if (!Hash::check($code, $otpHash)) {
            Cache::put(
                $this->attemptsKey($phone),
                $attempts + 1,
                now()->addMinutes(5)
            );

            return false;
        }

        // کد یک‌بارمصرف است
        Cache::forget($this->otpKey($phone));
        Cache::forget($this->lockKey($phone));
        Cache::forget($this->attemptsKey($phone));

        return true;
    }

    /**
     * ارسال پیامک با الگوی قاصدک
     */
    public function sendTemplate(
        string $phone,
        string $templateName,
        array $inputs,
        string $referenceId
    ): array {
        $apiKey = config('services.ghasedak.api_key');

        if (!$apiKey) {
            throw new RuntimeException('کلید API قاصدک تنظیم نشده است.');
        }

        $response = Http::withOptions([
            'verify' => 'C:/php-8.5.9/extras/ssl/cacert.pem',
            'timeout' => 20,
        ])->withHeaders([
                    'ApiKey' => $apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post($this->endpoint, [
                    'sendDate' => null,
                    'receptors' => [
                        [
                            'mobile' => $phone,
                            'clientReferenceId' => $referenceId,
                        ],
                    ],
                    'templateName' => $templateName,
                    'inputs' => $inputs,
                    'isVoice' => false,
                ]);

        if (!$response->successful()) {
            throw new RuntimeException(
                'ارسال پیامک ناموفق بود: ' .
                $response->status()
            );
        }

        return $response->json() ?? [];
    }
}
