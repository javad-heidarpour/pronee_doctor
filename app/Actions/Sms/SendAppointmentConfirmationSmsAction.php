<?php

namespace App\Actions\Sms;

use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use Throwable;

class SendAppointmentConfirmationSmsAction
{
    public function handle(int $visitNumber): JsonResponse
    {
        try {
            $ipResponse = Http::timeout(5)->get('https://api.ipify.org');
            $serverIp = $ipResponse->successful()
                ? trim($ipResponse->body())
                : 'دریافت نشد';
        } catch (Throwable $exception) {
            $serverIp = 'دریافت نشد';
        }

        $appointment = Appointment::find($visitNumber);

        if (!$appointment) {
            return response()->json([
                'server_outbound_ip' => $serverIp,
                'success' => false,
                'message' => 'نوبت موردنظر پیدا نشد.',
            ], 404);
        }

        $mobile = trim((string) $appointment->patient_phone);

        if (!preg_match('/^09\d{9}$/', $mobile)) {
            return response()->json([
                'server_outbound_ip' => $serverIp,
                'success' => false,
                'message' => 'شماره موبایل بیمار معتبر نیست.',
            ], 422);
        }

        $patientName = trim(
            ($appointment->patient_name ?? '') . ' ' .
            ($appointment->patient_family ?? '')
        );

        $date = Jalalian::fromDateTime($appointment->appointment_date)
            ->format('j F Y');
        $time = substr((string) $appointment->appointment_time, 0, 5);
        $appointmentDateTime = $date . ' ساعت ' . $time;

        $clinicAddress = 'مشهد، میدان تقی آباد، خیابان کوسنگی، خیابان پرستار';
        $cacheKey = 'appointment-confirmation-sms:' . $visitNumber;

        if (Cache::has($cacheKey)) {
            return response()->json([
                'server_outbound_ip' => $serverIp,
                'success' => false,
                'message' => 'پیامک قبلاً ارسال شده است. لطفاً ۳ دقیقه صبر کنید.',
                'visit_number' => $visitNumber,
            ], 429);
        }

        Cache::put($cacheKey, true, now()->addMinutes(3));

        try {
            $response = Http::withHeaders([
                'ApiKey' => config('services.ghasedak.api_key'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post(
                'https://gateway.ghasedak.me/rest/api/v1/WebService/SendOtpSMS',
                [
                    'sendDate' => null,
                    'receptors' => [[
                        'mobile' => $mobile,
                        'clientReferenceId' => 'visit-' . $visitNumber,
                    ]],
                    'templateName' => 'AppointmentConfirmationSMS',
                    'inputs' => [
                        ['param' => 'param1', 'value' => $patientName],
                        ['param' => 'param2', 'value' => $appointmentDateTime],
                        ['param' => 'param3', 'value' => $clinicAddress],
                    ],
                    'isVoice' => false,
                ]
            );

            if (!$response->successful()) {
                Cache::forget($cacheKey);

                return response()->json([
                    'server_outbound_ip' => $serverIp,
                    'success' => false,
                    'message' => 'ارسال پیامک ناموفق بود.',
                    'status' => $response->status(),
                    'error' => $response->json(),
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'پیامک تأیید نوبت با موفقیت ارسال شد.',
                'visit_number' => $visitNumber,
                'data' => $response->json('data'),
            ]);
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);

            Log::error('Appointment SMS Exception', [
                'visit_number' => $visitNumber,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'server_outbound_ip' => $serverIp,
                'success' => false,
                'message' => 'خطا در ارسال پیامک.',
                'debug_message' => $exception->getMessage(),
            ], 500);
        }
    }
}
