<?php

namespace App\Actions\Sms;

use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendVisitOtpSmsAction
{
    public function handle(int $visitNumber): JsonResponse
    {
        $sessionExpiry = (int) session()->get('sms.visit.allowed.' . $visitNumber, 0);

        abort_unless(
            $sessionExpiry >= now()->timestamp,
            403,
            'ارسال پیامک فقط از نشست ثبت‌کننده نوبت امکان‌پذیر است.'
        );

        $appointment = Appointment::query()->whereKey($visitNumber)->first();

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'نوبت موردنظر پیدا نشد.',
            ], 404);
        }

        if (
            $appointment->status !== 'pending' ||
            !$appointment->reservation_expires_at ||
            $appointment->reservation_expires_at->lessThanOrEqualTo(now())
        ) {
            return response()->json([
                'success' => false,
                'message' => 'مهلت رزرو این نوبت به پایان رسیده یا نوبت دیگر در انتظار پرداخت نیست.',
            ], 422);
        }

        $mobile = $appointment->patient_phone;

        if (!$mobile || !preg_match('/^09\d{9}$/', $mobile)) {
            return response()->json([
                'success' => false,
                'message' => 'شماره موبایل بیمار معتبر نیست.',
            ], 422);
        }

        if (!config('services.ghasedak.api_key')) {
            return response()->json([
                'success' => false,
                'message' => 'سرویس ارسال پیامک در حال حاضر پیکربندی نشده است.',
            ], 503);
        }

        $clientReferenceId = 'otp-' . $visitNumber;
        $cacheKey = 'otp-sms-lock:' . $visitNumber;

        if (!Cache::add($cacheKey, true, now()->addMinutes(3))) {
            return response()->json([
                'success' => false,
                'message' => 'پیامک قبلاً ارسال شده است. لطفاً ۳ دقیقه صبر کنید.',
                'visit_number' => $visitNumber,
            ], 429);
        }

        try {
            $response = Http::timeout(15)->withHeaders([
                'ApiKey' => config('services.ghasedak.api_key'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post(
                'https://gateway.ghasedak.me/rest/api/v1/WebService/SendOtpSMS',
                [
                    'sendDate' => null,
                    'receptors' => [[
                        'mobile' => $mobile,
                        'clientReferenceId' => $clientReferenceId,
                    ]],
                    'templateName' => 'Visit',
                    'inputs' => [[
                        'param' => 'Code',
                        'value' => trim((string) $visitNumber),
                    ]],
                    'isVoice' => false,
                ]
            );

            if (!$response->successful() || $response->json('IsSuccess') === false) {
                Cache::forget($cacheKey);

                Log::warning('Visit OTP SMS rejected by provider', [
                    'appointment_id' => $visitNumber,
                    'http_status' => $response->status(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'ارسال پیامک ناموفق بود. لطفاً بعداً تلاش کنید.',
                ], 422);
            }

            session()->forget('sms.visit.allowed.' . $visitNumber);

            return response()->json([
                'success' => true,
                'message' => 'پیامک ارسال شد. تا ۳ دقیقه امکان ارسال مجدد وجود ندارد.',
                'visit_number' => $visitNumber,
                'clientReferenceId' => $clientReferenceId,
            ]);
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیامک. لطفاً مجدداً تلاش کنید.',
            ], 500);
        }
    }
}
