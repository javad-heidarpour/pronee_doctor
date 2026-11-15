<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SmsController extends Controller
{

    public function visit(int $visit_number)
    {
        // جستجوی نوبت
        $appointment = Appointment::where('id', $visit_number)->first();

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'نوبت موردنظر پیدا نشد.',
            ], 404);
        }

        // دریافت شماره موبایل بیمار
        $mobile = $appointment->patient_phone;

        if (!$mobile || !preg_match('/^09\d{9}$/', $mobile)) {
            return response()->json([
                'success' => false,
                'message' => 'شماره موبایل بیمار معتبر نیست.',
            ], 422);
        }

        // شناسه ثابت برای جلوگیری از ارسال مجدد
        $clientReferenceId = 'otp-' . $visit_number;

        // کلید محدودیت ارسال برای هر نوبت
        $cacheKey = 'otp-sms-lock:' . $visit_number;

        // جلوگیری از ارسال پیامک تکراری تا ۳ دقیقه
        if (Cache::has($cacheKey)) {
            return response()->json([
                'success' => false,
                'message' => 'پیامک قبلاً ارسال شده است. لطفاً ۳ دقیقه صبر کنید.',
                'visit_number' => $visit_number,
            ], 429);
        }

        // قفل اولیه قبل از درخواست به سرویس پیامک
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

                        'receptors' => [
                            [
                                'mobile' => $mobile,
                                'clientReferenceId' => $clientReferenceId,
                            ],
                        ],

                        'templateName' => 'Visit',

                        'inputs' => [
                            [
                                'param' => 'Code',
                                'value' => trim((string) $visit_number),

                            ],
                        ],

                        'isVoice' => false,
                    ]
                );

            if (!$response->successful()) {
                Cache::forget($cacheKey);

                return response()->json([
                    'success' => false,
                    'status' => $response->status(),
                    'error' => $response->json(),
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'پیامک ارسال شد. تا ۳ دقیقه امکان ارسال مجدد وجود ندارد.',
                'visit_number' => $visit_number,
                'clientReferenceId' => $clientReferenceId,
                'data' => $response->json('data'),
            ]);

        } catch (\Throwable $e) {
            // در صورت بروز خطا، امکان تلاش مجدد فراهم شود
            Cache::forget($cacheKey);

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیامک. لطفاً مجدداً تلاش کنید.',
            ], 500);
        }
    }

    public function AppointmentConfirmationSMS(int $visit_number)
    {

        // دریافت IP عمومی خروجی سرور
        try {
            $ipResponse = Http::timeout(5)->get('https://api.ipify.org');

            $serverIp = $ipResponse->successful()
                ? trim($ipResponse->body())
                : 'دریافت نشد';
        } catch (\Throwable $e) {
            $serverIp = 'دریافت نشد';
        }

        $appointment = Appointment::find($visit_number);

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

        // نام و نام خانوادگی بیمار
        $patientName = trim(
            ($appointment->patient_name ?? '') . ' ' .
            ($appointment->patient_family ?? '')
        );

        // تاریخ و ساعت نوبت
        // نام فیلد appointment_date و appointment_time را
        // در صورت تفاوت با ستون‌های دیتابیس خود اصلاح کنید.
        $appointmentDate = $appointment->appointment_date;
        $appointmentTime = $appointment->appointment_time;

        // تبدیل تاریخ میلادی به شمسی
        $date = \Morilog\Jalali\Jalalian::fromDateTime($appointmentDate)
            ->format('j F Y');

        $time = substr((string) $appointmentTime, 0, 5);

        $appointmentDateTime = $date . ' ساعت ' . $time;

        // آدرس مطب
        $clinicAddress = 'مشهد، میدان تقی آباد، خیابان کوسنگی، خیابان پرستار';

        // محدودیت ارسال مجدد
        $cacheKey = 'appointment-confirmation-sms:' . $visit_number;

        if (Cache::has($cacheKey)) {
            return response()->json([
                 'server_outbound_ip' => $serverIp,
                'success' => false,
                'message' => 'پیامک قبلاً ارسال شده است. لطفاً ۳ دقیقه صبر کنید.',
                'visit_number' => $visit_number,
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

                        'receptors' => [
                            [
                                'mobile' => $mobile,
                                'clientReferenceId' => 'visit-' . $visit_number,
                            ],
                        ],

                        'templateName' => 'AppointmentConfirmationSMS',

                        'inputs' => [
                            [
                                'param' => 'param1',
                                'value' => $patientName,
                            ],
                            [
                                'param' => 'param2',
                                'value' => $appointmentDateTime,
                            ],
                            [
                                'param' => 'param3',
                                'value' => $clinicAddress,
                            ],
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
                'visit_number' => $visit_number,
                'data' => $response->json('data'),
            ]);


        } catch (\Throwable $e) {
            Cache::forget($cacheKey);

            \Log::error('Appointment SMS Exception', [
                'visit_number' => $visit_number,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                 'server_outbound_ip' => $serverIp,
                'success' => false,
                'message' => 'خطا در ارسال پیامک.',
                'debug_message' => $e->getMessage(),
            ], 500);
        }

    }
}