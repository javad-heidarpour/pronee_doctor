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
        $appointment = Appointment::query()
            ->with('clinic.city')
            ->find($visitNumber);

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'نوبت موردنظر پیدا نشد.',
            ], 404);
        }

        if (in_array($appointment->status, ['cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'برای نوبت لغوشده امکان ارسال پیامک تأیید وجود ندارد.',
            ], 422);
        }

        $mobile = trim((string) $appointment->patient_phone);

        if (!preg_match('/^09\d{9}$/', $mobile)) {
            return response()->json([
                'success' => false,
                'message' => 'شماره موبایل بیمار معتبر نیست.',
            ], 422);
        }

        $patientName = trim((string) $appointment->patient_name) ?: 'مراجع گرامی';
        $date = Jalalian::fromDateTime($appointment->appointment_date)->format('j F Y');
        $time = substr((string) $appointment->appointment_time, 0, 5);
        $appointmentDateTime = $date . ' ساعت ' . $time;

        $clinic = $appointment->clinic;
        $clinicAddress = trim(implode('، ', array_filter([
            $clinic?->city?->name,
            $clinic?->full_address,
        ])));

        if ($clinicAddress === '') {
            $clinicAddress = $clinic?->name ?: 'اطلاعات مطب در دسترس نیست';
        }

        $cacheKey = 'appointment-confirmation-sms:' . $visitNumber;

        // قفل اتمیک برای جلوگیری از ارسال هم‌زمان و تکراری.
        if (!Cache::add($cacheKey, true, now()->addMinutes(3))) {
            return response()->json([
                'success' => false,
                'message' => 'پیامک اخیراً ارسال شده است. لطفاً ۳ دقیقه صبر کنید.',
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

                Log::warning('Appointment confirmation SMS rejected by provider', [
                    'appointment_id' => $visitNumber,
                    'http_status' => $response->status(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'ارسال پیامک ناموفق بود. لطفاً بعداً تلاش کنید.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'پیامک تأیید نوبت با موفقیت ارسال شد.',
                'visit_number' => $visitNumber,
            ]);
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);

            Log::error('Appointment confirmation SMS exception', [
                'appointment_id' => $visitNumber,
                'exception' => get_class($exception),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیامک. لطفاً مجدداً تلاش کنید.',
            ], 500);
        }
    }
}
